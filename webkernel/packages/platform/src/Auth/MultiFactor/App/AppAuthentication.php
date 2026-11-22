<?php

namespace Webkernel\Platform\Auth\MultiFactor\App;

use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Auth\MultiFactor\App\Actions\DisableAppAuthenticationAction;
use Webkernel\Platform\Auth\MultiFactor\App\Actions\RegenerateAppAuthenticationRecoveryCodesAction;
use Webkernel\Platform\Auth\MultiFactor\App\Actions\SetUpAppAuthenticationAction;
use Webkernel\Platform\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Webkernel\Platform\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Webkernel\Platform\Auth\MultiFactor\Concerns\HasCacheStore;
use Webkernel\Platform\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Forms\Components\OneTimeCodeInput;
use Webkernel\Platform\Forms\Components\TextInput;
use Webkernel\Platform\Schemas\Components\Actions;
use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\Platform\Schemas\Components\Text;
use Webkernel\Platform\Schemas\Components\Utilities\Get;
use Webkernel\Platform\Schemas\Components\Utilities\Set;
use \Webkernel\Cache\Lock;
use \Webkernel\Cache\Repository;
use \Webkernel\Accounts\Contracts\Authenticatable;
use Webkernel\Utilities\Collection;
use \Webkernel\Cryptography\Hash;
use Webkernel\Utilities\Str;
use LogicException;
use PragmaRX\Google2FA\Google2FA;
use Webkernel\QrCode\Generator as QrCodeGenerator;
use SensitiveParameter;

use function Webkernel\Platform\Support\encode_key_segments;

class AppAuthentication implements MultiFactorAuthenticationProvider
{
    use HasCacheStore;

    protected bool $isRecoverable = false;

    protected bool $canRegenerateRecoveryCodes = true;

    protected int $recoveryCodeCount = 8;

    protected ?string $brandName = null;

    /**
     * 8 keys (respectively 4 minutes) past and future
     */
    protected int $codeWindow = 8;

    public function __construct(
        protected Google2FA $google2FA,
    ) {}

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'app';
    }

    public function getLoginFormLabel(): string
    {
        return __('webkernel-platform::auth/multi-factor/app/provider.login_form.label');
    }

    public function isEnabled(Authenticatable $user): bool
    {
        if (! ($user instanceof HasAppAuthentication)) {
            throw new LogicException('The user model must implement the [' . HasAppAuthentication::class . '] interface to use app authentication.');
        }

        return filled($user->getAppAuthenticationSecret());
    }

    public function getHolderName(HasAppAuthentication $user): string
    {
        return $user->getAppAuthenticationHolderName();
    }

    public function getSecret(HasAppAuthentication $user): string
    {
        $secret = $user->getAppAuthenticationSecret();

        if (blank($secret)) {
            throw new LogicException('The user does not have a app authentication secret.');
        }

        return $secret;
    }

    public function saveSecret(HasAppAuthentication $user, #[SensitiveParameter] ?string $secret): void
    {
        $user->saveAppAuthenticationSecret($secret);
    }

    /**
     * @return array<string>
     */
    public function getRecoveryCodes(HasAppAuthenticationRecovery $user): array
    {
        $codes = $user->getAppAuthenticationRecoveryCodes();

        if (blank($codes)) {
            throw new LogicException('The user does not have any app authentication recovery codes.');
        }

        return $codes;
    }

    /**
     * @param  array<string> | null  $codes
     */
    public function saveRecoveryCodes(HasAppAuthenticationRecovery $user, #[SensitiveParameter] ?array $codes): void
    {
        if (! is_array($codes)) {
            $user->saveAppAuthenticationRecoveryCodes(null);

            return;
        }

        $user->saveAppAuthenticationRecoveryCodes(array_map(
            fn (#[SensitiveParameter] string $code): string => Hash::make($code),
            $codes,
        ));
    }

    public function generateSecret(): string
    {
        return $this->google2FA->generateSecretKey(16);
    }

    public function getCurrentCode(HasAppAuthentication $user, #[SensitiveParameter] ?string $secret = null): string
    {
        return $this->google2FA->getCurrentOtp($secret ?? $this->getSecret($user));
    }

    public function generateQrCodeDataUri(#[SensitiveParameter] string $secret): string
    {
        /** @var HasAppAuthentication $user */
        $user = Platform::auth()->user();

        $uri = $this->google2FA->getQRCodeUrl(
            $this->getBrandName(),
            $this->getHolderName($user),
            $secret,
        );

        return (new QrCodeGenerator())->dataUri($uri);
    }

    /**
     * @return array<string>
     */
    public function generateRecoveryCodes(): array
    {
        return Collection::times($this->getRecoveryCodeCount(), fn (): string => Str::random(10) . '-' . Str::random(10))->all();
    }

    public function verifyCode(#[SensitiveParameter] string $code, #[SensitiveParameter] ?string $secret = null, bool $shouldPreventCodeReuse = false): bool
    {
        /** @var HasAppAuthentication $user */
        $user = Platform::auth()->user();

        $secret = $secret ?? $this->getSecret($user);

        if (! $shouldPreventCodeReuse) {
            return $this->google2FA->verifyKey($secret, $code, $this->getCodeWindow());
        }

        // The key deliberately excludes the code itself, so that it records the timestep of the
        // last accepted code rather than a marker for one specific code. RFC 6238 requires that
        // a successful verification rejects that code and every earlier one, not just a repeat
        // of the same code.
        $cacheKey = 'platform:auth:app_authentication:code:' . hash('sha256', $secret);
        $legacyCacheKey = 'platform.app_authentication_codes.' . md5($secret);
        $cache = $this->getCacheRepository();

        $verifyCode = function (?Lock $lock, Repository $cache) use ($cacheKey, $legacyCacheKey, $code, $secret): bool {
            // Passing an initial `$lastAcceptedTimestamp` makes `verifyKeyNewer()` return the
            // matched period instead of `true`, so future codes are cached correctly.
            $lastAcceptedTimestamp = $this->getLastAcceptedCodeTimestamp($cache, $cacheKey, $legacyCacheKey);

            if ($lastAcceptedTimestamp === false) {
                return false;
            }

            $lastAcceptedTimestamp ??= $this->google2FA->getTimestamp() - $this->getCodeWindow() - 1;

            $timestamp = $this->google2FA->verifyKeyNewer($secret, $code, $lastAcceptedTimestamp, $this->getCodeWindow());

            if ($timestamp === false) {
                return false;
            }

            $lastAcceptedTimestamp = $this->getLastAcceptedCodeTimestamp($cache, $cacheKey, $legacyCacheKey);

            if (
                ($lastAcceptedTimestamp === false)
                || (($lastAcceptedTimestamp !== null) && ($timestamp <= $lastAcceptedTimestamp))
                || (($lock !== null) && (! $lock->isOwnedByCurrentProcess()))
            ) {
                return false;
            }

            // Preserve replay protection for workers still using the old key during deployment.
            return $cache->forever($legacyCacheKey, $timestamp) && $cache->forever($cacheKey, $timestamp);
        };

        return $this->executeWithCacheLock($cache, "{$cacheKey}:lock", $verifyCode);
    }

    protected function getLastAcceptedCodeTimestamp(Repository $cache, string $cacheKey, string $legacyCacheKey): int | false | null
    {
        $lastAcceptedTimestamp = null;

        foreach ([$cacheKey, $legacyCacheKey] as $key) {
            $timestamp = $cache->get($key);

            if (is_string($timestamp) && ctype_digit($timestamp)) {
                $timestamp = (int) $timestamp;
            }

            if ($timestamp === null) {
                continue;
            }

            if (! is_int($timestamp)) {
                return false;
            }

            $lastAcceptedTimestamp = ($lastAcceptedTimestamp === null) ? $timestamp : max($lastAcceptedTimestamp, $timestamp);
        }

        return $lastAcceptedTimestamp;
    }

    public function verifyRecoveryCode(#[SensitiveParameter] string $recoveryCode, ?HasAppAuthenticationRecovery $user = null): bool
    {
        $user ??= Platform::auth()->user();

        $lockKey = 'platform:auth:app_authentication:recovery_codes:' . hash(
            'sha256',
            encode_key_segments($user::class, (string) (($user instanceof Authenticatable) ? $user->getAuthIdentifier() : spl_object_id($user))),
        ) . ':lock';

        $cache = $this->getCacheRepository();
        $connection = $user->getConnection(); /** @phpstan-ignore-line */

        return $this->executeWithCacheLock($cache, $lockKey, fn (?Lock $lock): bool => $connection->transaction(function () use ($lock, $user, $recoveryCode): bool {
            $lockedUser = $user
                ->newQuery() /** @phpstan-ignore-line */
                ->whereKey($user->getKey()) /** @phpstan-ignore-line */
                ->lockForUpdate()
                ->first();

            if ($lockedUser === null) {
                return false;
            }

            $remainingCodes = [];
            $isValid = false;

            foreach ($this->getRecoveryCodes($lockedUser) as $hashedRecoveryCode) { /** @phpstan-ignore-line */
                if (Hash::check($recoveryCode, $hashedRecoveryCode)) {
                    $isValid = true;

                    continue;
                }

                $remainingCodes[] = $hashedRecoveryCode;
            }

            if (! $isValid) {
                return false;
            }

            if (($lock !== null) && (! $lock->isOwnedByCurrentProcess())) {
                return false;
            }

            $lockedUser->saveAppAuthenticationRecoveryCodes($remainingCodes); /** @phpstan-ignore-line */

            return true;
        }));
    }

    /**
     * @return array<Component | Action | ActionGroup>
     */
    public function getManagementSchemaComponents(): array
    {
        $user = Platform::auth()->user();

        return [
            Actions::make($this->getActions())
                ->label(__('webkernel-platform::auth/multi-factor/app/provider.management_schema.actions.label'))
                ->belowContent(__('webkernel-platform::auth/multi-factor/app/provider.management_schema.actions.below_content'))
                ->afterLabel(fn (): Text => $this->isEnabled($user)
                    ? Text::make(__('webkernel-platform::auth/multi-factor/app/provider.management_schema.actions.messages.enabled'))
                        ->badge()
                        ->color('success')
                    : Text::make(__('webkernel-platform::auth/multi-factor/app/provider.management_schema.actions.messages.disabled'))
                        ->badge()),
        ];
    }

    /**
     * @return array<Action>
     */
    public function getActions(): array
    {
        $user = Platform::auth()->user();

        return [
            SetUpAppAuthenticationAction::make($this)
                ->hidden(fn (): bool => $this->isEnabled($user)),
            RegenerateAppAuthenticationRecoveryCodesAction::make($this)
                ->visible(fn (): bool => $this->isEnabled($user) && $this->isRecoverable() && $this->canRegenerateRecoveryCodes()),
            DisableAppAuthenticationAction::make($this)
                ->visible(fn (): bool => $this->isEnabled($user)),
        ];
    }

    public function recoverable(bool $condition = true): static
    {
        $this->isRecoverable = $condition;

        return $this;
    }

    public function isRecoverable(): bool
    {
        return $this->isRecoverable;
    }

    public function regenerableRecoveryCodes(bool $condition = true): static
    {
        $this->canRegenerateRecoveryCodes = $condition;

        return $this;
    }

    public function canRegenerateRecoveryCodes(): bool
    {
        return $this->canRegenerateRecoveryCodes;
    }

    public function brandName(?string $brandName): static
    {
        $this->brandName = $brandName;

        return $this;
    }

    public function getBrandName(): string
    {
        return $this->brandName ?? strip_tags(Platform::getBrandName());
    }

    public function recoveryCodeCount(int $count): static
    {
        $this->recoveryCodeCount = $count;

        return $this;
    }

    public function getRecoveryCodeCount(): int
    {
        return $this->recoveryCodeCount;
    }

    public function codeWindow(int $window): static
    {
        $this->codeWindow = $window;

        return $this;
    }

    public function getCodeWindow(): int
    {
        return $this->codeWindow;
    }

    /**
     * @param  Authenticatable&HasAppAuthentication&HasAppAuthenticationRecovery  $user
     */
    public function getChallengeFormComponents(Authenticatable $user): array
    {
        $isRecoverable = $this->isRecoverable();

        return [
            OneTimeCodeInput::make('code')
                ->label(__('webkernel-platform::auth/multi-factor/app/provider.login_form.code.label'))
                ->submitOnCompletion()
                ->belowContent(fn (Get $get): Action => Action::make('useRecoveryCode')
                    ->label(__('webkernel-platform::auth/multi-factor/app/provider.login_form.code.actions.use_recovery_code.label'))
                    ->link()
                    ->action(fn (Set $set) => $set('useRecoveryCode', true))
                    ->visible(fn (): bool => $isRecoverable && (! $get('useRecoveryCode'))))
                ->validationAttribute(__('webkernel-platform::auth/multi-factor/app/provider.login_form.code.validation_attribute'))
                ->required(fn (Get $get): bool => (! $isRecoverable) || (! $get('useRecoveryCode')) || blank($get('recoveryCode')))
                ->rule(function () use ($user): Closure {
                    return function (string $attribute, #[SensitiveParameter] $value, Closure $fail) use ($user): void {
                        if ($this->verifyCode($value, $this->getSecret($user), shouldPreventCodeReuse: true)) {
                            return;
                        }

                        $fail(__('webkernel-platform::auth/multi-factor/app/provider.login_form.code.messages.invalid'));
                    };
                }),
            TextInput::make('recoveryCode')
                ->label(__('webkernel-platform::auth/multi-factor/app/provider.login_form.recovery_code.label'))
                ->validationAttribute(__('webkernel-platform::auth/multi-factor/app/provider.login_form.recovery_code.validation_attribute'))
                ->password()
                ->revealable(Platform::arePasswordsRevealable())
                ->autocomplete('one-time-code')
                ->rule(function () use ($user): Closure {
                    return function (string $attribute, #[SensitiveParameter] mixed $value, Closure $fail) use ($user): void {
                        if (blank($value)) {
                            return;
                        }

                        if (is_string($value) && $this->verifyRecoveryCode($value, $user)) {
                            return;
                        }

                        $fail(__('webkernel-platform::auth/multi-factor/app/provider.login_form.recovery_code.messages.invalid'));
                    };
                })
                ->visible(fn (Get $get): bool => $isRecoverable && $get('useRecoveryCode'))
                ->live(onBlur: true),
        ];
    }
}
