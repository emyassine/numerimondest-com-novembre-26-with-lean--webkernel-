<?php

namespace Webkernel\Platform\Notifications;

use Closure;
use \Webkernel\Cache\FileStore;
use \Webkernel\Cache\Contracts\LockProvider;
use \Webkernel\Cache\Contracts\Repository;
use \Webkernel\Cache\Cache;
use Webkernel\Utilities\Str;
use RuntimeException;

use function Webkernel\Platform\Support\encode_key_segments;

class NotificationMailbox
{
    protected const MAX_PENDING_NOTIFICATIONS = 50;

    /** @var array<array{notification: array<string, mixed>, batch?: ?string}> */
    protected array $pendingNotifications = [];

    /** @param array<string, mixed> $notification */
    public function push(array $notification): void
    {
        $this->initializeScope();

        $this->pendingNotifications[] = ['notification' => $notification];

        if (count($this->pendingNotifications) > static::MAX_PENDING_NOTIFICATIONS) {
            array_shift($this->pendingNotifications);
        }
    }

    public function initializeScope(): void
    {
        // Webkernel stores CSRF tokens under its own reserved session key. Do
        // not look for Laravel's `_token` key and rotate the active token.
        session()->token();

        // Existing session entropy makes concurrent initialization deterministic,
        // without allowing a revoked cookie to reconstruct the mailbox address.
        if (! session()->has('platform.notifications.mailbox')) {
            $this->pendingNotifications = [];

            session()->put('platform.notifications.mailbox', hash_hmac(
                'sha256',
                encode_key_segments(session()->getId(), session()->token()),
                config('app.key'),
            ));
        }
    }

    public function rotateScope(bool $shouldPreservePublishedNotifications = false): void
    {
        $this->initializeScope();

        if ($shouldPreservePublishedNotifications) {
            $this->pendingNotifications = array_slice([...$this->pullEntries(), ...$this->pendingNotifications], -static::MAX_PENDING_NOTIFICATIONS);
        }

        session()->put('platform.notifications.mailbox', Str::random(40));
    }

    public function hasPendingNotifications(): bool
    {
        return count($this->pendingNotifications) > 0;
    }

    public function hasPublishedNotifications(): bool
    {
        $cache = Cache::store(config('platform.cache_store'));
        $hasUnownedNotifications = static fn (Repository $cache, string $key): bool => collect($cache->get($key) ?? [])
            ->contains(static fn (array $entry): bool => ! isset($entry['batch']));

        // `FileStore::get()` can delete expired entries after reading them, so an
        // unlocked read could delete notifications published in the meantime.
        if ($cache->getStore() instanceof FileStore) {
            return $this->mutate($hasUnownedNotifications);
        }

        return $hasUnownedNotifications($cache, $this->getCacheKey());
    }

    public function flush(?string $batch = null): void
    {
        if (! $this->hasPendingNotifications()) {
            return;
        }

        $this->mutate(function (Repository $cache, string $key) use ($batch): void {
            if (! $cache->put(
                $key,
                array_slice([
                    ...($cache->get($key) ?? []),
                    ...array_map(
                        static fn (array $entry): array => $entry + ['batch' => $batch],
                        $this->pendingNotifications,
                    ),
                ], -static::MAX_PENDING_NOTIFICATIONS),
                now()->addMinutes(max(1, (int) config('session.lifetime', 120))),
            )) {
                throw new RuntimeException('Could not store Platform notifications in the mailbox.');
            }
        });

        $this->pendingNotifications = [];
    }

    /**
     * @param  array<string>  $batches
     * @return array<array<string, mixed>>
     */
    public function pull(array $batches = []): array
    {
        return array_map(
            static fn (array $entry): array => $entry['notification'],
            $this->pullEntries($batches),
        );
    }

    /**
     * @param  ?array<string>  $batches
     * @return array<array{notification: array<string, mixed>, batch: ?string}>
     */
    protected function pullEntries(?array $batches = null): array
    {
        return $this->mutate(function (Repository $cache, string $key) use ($batches): array {
            $notifications = [];
            $remaining = [];

            foreach ($cache->get($key) ?? [] as $entry) {
                // Mailboxes published before response ownership was introduced
                // contain bare notifications, which remain unowned catch-up work.
                if (! array_key_exists('notification', $entry)) {
                    $entry = ['notification' => $entry, 'batch' => null];
                }

                if (($batches === null) || ($entry['batch'] === null) || in_array($entry['batch'], $batches, strict: true)) {
                    $notifications[] = $entry;
                } else {
                    $remaining[] = $entry;
                }
            }

            if (count($notifications)) {
                $wasStored = count($remaining)
                    ? $cache->put($key, $remaining, now()->addMinutes(max(1, (int) config('session.lifetime', 120))))
                    : $cache->forget($key);

                if (! $wasStored) {
                    throw new RuntimeException('Could not remove Platform notifications from the mailbox.');
                }
            }

            return $notifications;
        });
    }

    protected function getScope(): string
    {
        $this->initializeScope();

        return session()->get('platform.notifications.mailbox');
    }

    protected function getCacheKey(): string
    {
        return 'platform:notifications:' . $this->getScope();
    }

    protected function mutate(Closure $callback): mixed
    {
        $cache = Cache::store(config('platform.cache_store'));
        $store = $cache->getStore();
        $key = $this->getCacheKey();

        if (! $store instanceof LockProvider) {
            return $callback($cache, $key);
        }

        // Only cache operations run under this lock, never application work,
        // rendering, or waiting for the browser to receive the response.
        return $store->lock("{$key}:lock", 10)->block(5, fn (): mixed => $callback($cache, $key));
    }
}
