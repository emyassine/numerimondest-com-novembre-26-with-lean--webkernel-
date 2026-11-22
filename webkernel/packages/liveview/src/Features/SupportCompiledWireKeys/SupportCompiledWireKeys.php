<?php

namespace Webkernel\Liveview\Features\SupportCompiledWireKeys;

use Webkernel\Liveview\ComponentHook;
use Webkernel\Config\Config;
use Webkernel\View\View as WebkernelView;

use function Webkernel\Liveview\on;

class SupportCompiledWireKeys extends ComponentHook
{
    public static $loopStack = [];
    public static $currentLoop = [
        'count' => null,
        'index' => null,
        'key' => null,
    ];

    public static function provide()
    {
        on('flush-state', function () {
            static::$loopStack = [];
            static::$currentLoop = [
                'count' => null,
                'index' => null,
                'key' => null,
            ];
        });

        if (! Config::get('liveview.smart_wire_keys', true)) {
            return;
        }

        static::registerPrecompilers();
    }

    public static function registerPrecompilers()
    {
        WebkernelView::prepareViewsForCompilationUsing(function ($contents) {
            $contents = static::compile($contents);

            return $contents;
        });
    }

    public static function compile($contents)
    {
        // Strip out all livewire tag components as we don't want to match any of them...
        $placeholder = '<__livewire-component-placeholder__>';
        $cleanedContents = preg_replace('/<livewire:[^>]+?\/>/is', $placeholder, $contents);

        // Handle `wire:key` attributes on elements...
        preg_match_all('/(?<=\s)wire:key\s*=\s*(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\')/', $cleanedContents, $keys);

        foreach ($keys[0] as $index => $key) {
            $keyExpression = static::compileKeyExpression($keys[1][$index]);
            $prefix = "<?php \Webkernel\Liveview\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::\$currentLoop['key'] = {$keyExpression}; ?>";
            $contents = str_replace($key, $prefix . $key, $contents);
        }

        // Handle `wire:key` attributes on component tags.
        $contents = preg_replace(
            '/(<\?php\s+\$component->withAttributes\(\[.*?\]\);\s*\?>)/s',
            "$1\n<?php \Webkernel\Liveview\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey(\$component); ?>\n",
            $contents
        );

        return $contents;
    }

    public static function openLoop() {
        if (static::$currentLoop['count'] === null) {
            static::$currentLoop['count'] = 0;
        } else {
            static::$currentLoop['count']++;
        }

        static::$loopStack[] = static::$currentLoop;

        static::$currentLoop = [
            'count' => null,
            'index' => null,
            'key' => null,
        ];
    }

    // Increment the template loop index without depending on a loop helper
    // variable that may not exist for every loop directive.
    public static function startLoopIteration() {
        if (static::$currentLoop['index'] === null) {
            static::$currentLoop['index'] = 0;
        } else {
            static::$currentLoop['index']++;
        }
    }

    /**
     * @deprecated Use startLoopIteration() instead. Kept for cached compiled views.
     */
    public static function startLoop($index) {
        static::$currentLoop['index'] = $index;
    }

    public static function endLoop() {
        static::$currentLoop = [
            'count' => null,
            'index' => static::$currentLoop['index'] ?? null,
            'key' => null,
        ];
    }

    public static function closeLoop() {
        static::$currentLoop = array_pop(static::$loopStack);
    }

    public static function compileKeyExpression($keyString)
    {
        // Compile template echo statements into PHP string concatenation.
        $value = WebkernelView::compiler()->compile_string($keyString, 'inline-wire-key');

        // Escape single quotes only outside of PHP blocks...
        $tokens = array_slice(token_get_all('<'.'?php ?'.'>'.$value), 2);
        $compiled = '';
        foreach ($tokens as $token) {
            $text = is_array($token) ? $token[1] : $token;
            $compiled .= is_array($token) && $token[0] === T_INLINE_HTML
                ? str_replace("'", "\\'", $text)
                : $text;
        }
        $value = $compiled;

        $value = str_replace('<'.'?php echo ', "'.", $value);
        $value = str_replace('; ?'.'>', ".'", $value);

        return "'".$value."'";
    }

    public static function processComponentKey($component)
    {
        if ($component->attributes->has('wire:key')) {
            static::$currentLoop['key'] = $component->attributes->get('wire:key');
        }
    }

    public static function generateKey($deterministicViewKey, $key = null)
    {
        $finalKey = $deterministicViewKey;

        $loops = array_merge(static::$loopStack, [static::$currentLoop]);

        foreach ($loops as $loop) {
            if (isset($loop['key']) || isset($loop['index'])) {
                $finalKey .= isset($loop['key'])
                    ? '-' . $loop['key']
                    : '-' . $loop['index'];
            }

            if (isset($loop['count'])) {
                $finalKey .= '-' . $loop['count'];
            }
        }

        if (isset($key) && $key !== '') {
            $finalKey .= '-' . $key;
        }

        return $finalKey;
    }
}
