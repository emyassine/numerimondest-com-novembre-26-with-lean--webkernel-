<?php

namespace Webkernel\Liveview\Features\SupportValidation;

use Webkernel\Liveview\Drawer\Utils;
use \Webkernel\Validation\ValidationException;
use Webkernel\Utilities\ViewErrorBag;
use Webkernel\Liveview\ComponentHook;
use function Webkernel\Liveview\on;

class SupportValidation extends ComponentHook
{
    public static $view;

    public static function provide()
    {
        static::$view = null;

        on('flush-state', function () {
            static::$view = null;
        });
    }

    function hydrate($memo)
    {
        $this->component->setErrorBag(
            $memo['errors'] ?? []
        );
    }

    function render($view, $data)
    {
        $errors = $this->viewErrorBag();

        $revert = Utils::shareWithViews('errors', $errors);

        return function () use ($revert) {
            // After the component has rendered, let's revert our global
            // sharing the "errors" variable with component templates.
            $revert();
        };
    }

    function renderIsland($name, $view, $data)
    {
        $errors = $this->viewErrorBag();

        $revert = Utils::shareWithViews('errors', $errors);

        return function () use ($revert) {
            $revert();
        };
    }

    function dehydrate($context)
    {
        $errors = $this->component->getErrorBag()->toArray();

        // Only persist errors that were born from properties on the component
        // and not from custom validators (Validator::make) that were run.
        $context->addMemo('errors', collect($errors)
            ->filter(function ($value, $key) {
                return Utils::hasProperty($this->component, $key);
            })
            ->toArray()
        );
    }

    function exception($e, $stopPropagation)
    {
        if (! $e instanceof ValidationException) return;

        $this->component->setErrorBag($e->validator->errors());

        $stopPropagation();
    }

    protected function viewErrorBag(): ViewErrorBag
    {
        $previouslySharedErrors = \Webkernel\View\View::shared('errors');

        $errors = $previouslySharedErrors instanceof ViewErrorBag
            ? clone $previouslySharedErrors
            : new ViewErrorBag;

        return $errors->put('default', $this->component->getErrorBag());
    }
}
