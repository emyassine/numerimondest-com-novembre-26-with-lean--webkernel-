<?php

namespace Webkernel\Liveview\Features\SupportTesting;

use \Webkernel\Validation\ValidationException;
use Webkernel\Liveview\ComponentHook;
use Webkernel\Liveview\Component;

class SupportTesting extends ComponentHook
{
    static function provide()
    {
        if (! app()->environment('testing')) return;

        static::registerTestingMacros();
        static::syncCompilerCacheDirectoryDuringParallelTesting();
    }

    protected static function syncCompilerCacheDirectoryDuringParallelTesting()
    {
        // `Parallel::setUpTestCase` changes the compiled-view path after the
        // application has booted. If the
        // `livewire.compiler` singleton was already resolved by that point, its
        // `CacheManager::$cacheDirectory` retains the original path, which causes
        // every parallel worker to share the same cache directory and race when
        // writing islands to it. Patch the cache directory whenever the worker's
        // compiled-view path changes. See https://github.com/livewire/livewire/issues/10262.
        if (! class_exists(\Webkernel\Testing\Parallel::class)) return;

        \Webkernel\Testing\Parallel::setUpTestCase(function () {
            if (! app()->resolved('liveview.compiler')) return;

            app('liveview.compiler')->cacheManager->cacheDirectory =
                rtrim(config('view.compiled'), '/\\') . '/livewire';
        });
    }

    function dehydrate($context)
    {
        $target = $this->component;

        $errors = $target->getErrorBag();

        if (! $errors->isEmpty()) {
            $this->storeSet('testing.errors', $errors);
        }
    }

    function hydrate()
    {
        $this->storeSet('testing.validator', null);
    }

    function exception($e, $stopPropagation) {
        if (! $e instanceof ValidationException) return;

        $this->storeSet('testing.validator', $e->validator);
    }

    protected static function registerTestingMacros()
    {
        // Usage: $this->assertSeeLivewire('counter');
        \Webkernel\Testing\TestResponse::macro('assertSeeLivewire', function ($component) {
            if (is_subclass_of($component, Component::class)) {
                $component = app('liveview.factory')->resolveComponentName($component);
            }
            $escapedComponentName = trim(htmlspecialchars(json_encode(['name' => $component])), '{}');

            \PHPUnit\Framework\Assert::assertStringContainsString(
                $escapedComponentName,
                $this->getContent(),
                'Cannot find Livewire component ['.$component.'] rendered on page.'
            );

            return $this;
        });

        // Usage: $this->assertDontSeeLivewire('counter');
        \Webkernel\Testing\TestResponse::macro('assertDontSeeLivewire', function ($component) {
            if (is_subclass_of($component, Component::class)) {
                $component = app('liveview.factory')->resolveComponentName($component);
            }
            $escapedComponentName = trim(htmlspecialchars(json_encode(['name' => $component])), '{}');

            \PHPUnit\Framework\Assert::assertStringNotContainsString(
                $escapedComponentName,
                $this->getContent(),
                'Found Livewire component ['.$component.'] rendered on page.'
            );

            return $this;
        });

        if (class_exists(\Webkernel\Testing\TestView::class)) {
            \Webkernel\Testing\TestView::macro('assertSeeLivewire', function ($component) {
                if (is_subclass_of($component, Component::class)) {
                    $component = app('liveview.factory')->resolveComponentName($component);
                }
                $escapedComponentName = trim(htmlspecialchars(json_encode(['name' => $component])), '{}');

                \PHPUnit\Framework\Assert::assertStringContainsString(
                    $escapedComponentName,
                    $this->rendered,
                    'Cannot find Livewire component ['.$component.'] rendered on page.'
                );

                return $this;
            });

            \Webkernel\Testing\TestView::macro('assertDontSeeLivewire', function ($component) {
                if (is_subclass_of($component, Component::class)) {
                    $component = app('liveview.factory')->resolveComponentName($component);
                }
                $escapedComponentName = trim(htmlspecialchars(json_encode(['name' => $component])), '{}');

                \PHPUnit\Framework\Assert::assertStringNotContainsString(
                    $escapedComponentName,
                    $this->rendered,
                    'Found Livewire component ['.$component.'] rendered on page.'
                );

                return $this;
            });
        }
    }
}
