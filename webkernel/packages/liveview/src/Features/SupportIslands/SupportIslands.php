<?php

namespace Webkernel\Liveview\Features\SupportIslands;

use Webkernel\Liveview\Features\SupportIslands\Compiler\IslandCompiler;
use Webkernel\Liveview\ComponentHook;
use Webkernel\View\View;

use function Webkernel\Liveview\before;

class SupportIslands extends ComponentHook
{
    public static function provide()
    {
        static::registerInlineIslandPrecompiler();
        static::registerIslandDirective();

        // Flush implicit island renders before any dehydrate hook, so other hooks (e.g. assets) see their output...
        before('dehydrate', function ($component) {
            $component->renderImplicitIslandMorphs();
        });
    }

    public static function registerIslandDirective()
    {
        View::directive('island', static fn (string $expression): string =>
            '<?php if (isset($__webkernel_component)) { echo $__webkernel_component->renderIslandDirective'.$expression.'; } ?>'
        );
    }

    public static function registerInlineIslandPrecompiler()
    {
        View::prepareViewsForCompilationUsing(static function (string $content, string $path): string {
            if (! str_contains($content, '@island')) { return $content; }

            return IslandCompiler::compile($path, $content);
        });
    }

    function call($method, $params, $returnEarly, $metadata, $componentContext)
    {
        if (! isset($metadata['island'])) return;

        $island = $metadata['island'];

        $mount = false;

        if ($method === '__lazyLoadIsland') {
            $mount = true;
            $returnEarly();
        }

        // if metadata contains an island, then we should render it...
        return function (...$params) use ($method, $island, $componentContext, $mount, $metadata) {
            ['name' => $name, 'mode' => $mode] = $island;

            $islands = $this->component->getIslands();

            $islands = array_filter($islands, fn ($island) => $island['name'] === $name);

            if (empty($islands)) return;

            // Renderless attributes and modifiers only apply to their own call...
            if (($metadata['renderless'] ?? false) || $this->component->isRenderlessMethod($method)) {
                return;
            }

            $this->component->triggerImplicitIslandRender(
                name: $name,
                mode: $mode,
                mount: $mount,
            );
        };
    }

    public function dehydrate($context)
    {
        $this->component->renderImplicitIslandMorphs();

        $context->addMemo('islands', $this->component->getIslands());

        if ($this->component->hasRenderedIslandFragments()) {
            $context->addEffect('islandFragments', $this->component->getRenderedIslandFragments());
        }
    }

    public function hydrate($memo)
    {
        if (($memo['lazyLoaded'] ?? null) === false) return;

        $this->component->markIslandsAsMounted();

        $islands = $memo['islands'] ?? null;

        if (! $islands) return;

        $this->component->setIslands($islands ?? []);
    }
}
