<?php

namespace Webkernel\Platform\Auth\MultiFactor\Pages;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;
use Webkernel\Platform\Auth\MultiFactor\MultiFactorChallenge;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Pages\SimplePage;
use Webkernel\Platform\Schemas\Components\Actions;
use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\Platform\Schemas\Components\Group;
use Webkernel\Platform\Schemas\Components\Section;
use Webkernel\Platform\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Webkernel\Platform\Schemas\Schema;
use Webkernel\View\Htmlable;

class SetUpRequiredMultiFactorAuthentication extends SimplePage
{
    use RestrictsFileUploadsToSchemaComponents;

    public function mount(): void
    {
        if ((! Platform::hasMultiFactorAuthentication()) || $this->isEnabled()) {
            redirect()->intended(Platform::getProfileUrl() ?? Platform::getUrl());
        }
    }

    public function getTitle(): string | Htmlable
    {
        return __('webkernel-platform::auth/multi-factor/pages/set-up-required-multi-factor-authentication.title');
    }

    public function getHeading(): string | Htmlable | null
    {
        return __('webkernel-platform::auth/multi-factor/pages/set-up-required-multi-factor-authentication.heading');
    }

    public function getSubheading(): string | Htmlable | null
    {
        return __('webkernel-platform::auth/multi-factor/pages/set-up-required-multi-factor-authentication.subheading');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getMultiFactorAuthenticationContentComponent(),
                $this->getFooterActionsContentComponent(),
            ]);
    }

    public function getMultiFactorAuthenticationContentComponent(): Component
    {
        $user = Platform::auth()->user();

        return Section::make()
            ->compact()
            ->divided()
            ->secondary()
            ->schema(collect(Platform::getMultiFactorAuthenticationProviders())
                ->sort(fn (MultiFactorAuthenticationProvider $multiFactorAuthenticationProvider): int => $multiFactorAuthenticationProvider->isEnabled($user) ? 0 : 1)
                ->map(fn (MultiFactorAuthenticationProvider $multiFactorAuthenticationProvider): Component => Group::make($multiFactorAuthenticationProvider->getManagementSchemaComponents())
                    ->statePath($multiFactorAuthenticationProvider->getId()))
                ->all());
    }

    public function getFooterActionsContentComponent(): Component
    {
        return Actions::make($this->getFooterActions())
            ->fullWidth();
    }

    /**
     * @return array<Action>
     */
    public function getFooterActions(): array
    {
        return [
            $this->getContinueAction(),
        ];
    }

    public function getContinueAction(): Action
    {
        return Action::make('continue')
            ->label(__('webkernel-platform::auth/multi-factor/pages/set-up-required-multi-factor-authentication.actions.continue.label'))
            ->action(fn () => redirect()->intended(Platform::getUrl()))
            ->visible($this->isEnabled(...));
    }

    public function isEnabled(): bool
    {
        return MultiFactorChallenge::make()->hasEnabledProviders(Platform::auth()->user());
    }
}
