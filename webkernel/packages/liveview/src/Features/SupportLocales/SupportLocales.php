<?php

namespace Webkernel\Liveview\Features\SupportLocales;

use Webkernel\Liveview\ComponentHook;

class SupportLocales extends ComponentHook
{
    function hydrate($memo)
    {
        if ($locale = $memo['locale']) \Webkernel\I18n\I18nContext::set_locale($locale);
    }

    function dehydrate($context)
    {
        $context->addMemo('locale', \Webkernel\I18n\I18nContext::get_locale());
    }
}
