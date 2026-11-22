<?php

namespace Webkernel\Platform\Forms\Components\TextInput\Actions;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Forms\View\FormsIconAlias;
use Webkernel\Platform\Support\Facades\PlatformIcon;
use Webkernel\Platform\Support\Icons\Heroicon;

class ShowPasswordAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'showPassword';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('webkernel-forms::components.text_input.actions.show_password.label'));

        $this->icon(PlatformIcon::resolve(FormsIconAlias::COMPONENTS_TEXT_INPUT_ACTIONS_SHOW_PASSWORD) ?? Heroicon::Eye);

        $this->defaultColor('gray');

        $this->extraAttributes([
            'wire:loading.attr' => false,
            'x-ref' => 'showPasswordAction',
            'x-show' => '! isPasswordRevealed',
        ], merge: true);

        $this->alpineClickHandler('setPasswordRevealed(true)');
    }
}
