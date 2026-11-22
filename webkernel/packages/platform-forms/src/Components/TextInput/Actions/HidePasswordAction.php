<?php

namespace Webkernel\Platform\Forms\Components\TextInput\Actions;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Forms\View\FormsIconAlias;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;

class HidePasswordAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'hidePassword';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('webkernel-forms::components.text_input.actions.hide_password.label'));

        $this->icon(PlatformIcon::resolve(FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_HIDE_PASSWORD) ?? Heroicon::EyeSlash);

        $this->defaultColor('gray');

        $this->extraAttributes([
            'wire:loading.attr' => false,
            'x-cloak' => 'x-cloak',
            'x-ref' => 'hidePasswordAction',
            'x-show' => 'isPasswordRevealed',
        ], merge: true);

        $this->alpineClickHandler('setPasswordRevealed(false)');
    }
}
