<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Notifications\Events;

use Webkernel\Broadcasting\Contracts\ShouldBroadcast;
use Webkernel\Broadcasting\PrivateChannel;
use Webkernel\Queue\Bus\Queueable;
use Webkernel\Queue\SerializesModels;

final class BroadcastNotificationCreated implements ShouldBroadcast
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public mixed $notifiable,
        public object $notification,
        public array $data = [],
    ) {
    }

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        $channels = method_exists($this->notification, 'broadcastOn')
            ? $this->notification->broadcastOn()
            : [];

        if (! is_array($channels)) {
            $channels = $channels === null || $channels === '' ? [] : [$channels];
        }

        if ($channels === [] && method_exists($this->notifiable, 'receivesBroadcastNotificationsOn')) {
            $channels = [$this->notifiable->receivesBroadcastNotificationsOn($this->notification)];
        }

        if ($channels === [] && method_exists($this->notifiable, 'getKey')) {
            $channels = [str_replace('\\', '.', $this->notifiable::class).'.'.$this->notifiable->getKey()];
        }

        return array_map(
            static fn (PrivateChannel|string $channel): PrivateChannel => $channel instanceof PrivateChannel ? $channel : new PrivateChannel($channel),
            $channels,
        );
    }

    public function broadcastWith(): array
    {
        if (method_exists($this->notification, 'broadcastWith')) {
            return $this->notification->broadcastWith();
        }

        return [...$this->data, 'id' => $this->notification->id ?? null, 'type' => $this->broadcastType()];
    }

    public function broadcastType(): string
    {
        return method_exists($this->notification, 'broadcastType')
            ? $this->notification->broadcastType()
            : $this->notification::class;
    }

    public function broadcastAs(): string
    {
        return method_exists($this->notification, 'broadcastAs')
            ? $this->notification->broadcastAs()
            : self::class;
    }
}
