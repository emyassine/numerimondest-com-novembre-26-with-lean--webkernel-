<?php declare(strict_types=1);

namespace Webkernel\Liveview\Webkernel;

use Webkernel\View\Compile\State;

/** Compiles Liveview component, script, and asset blocks for Webkernel views. */
final class ViewDirectives
{
    public static function liveview(string $expression, State $state): string
    {
        $arguments = trim(\Webkernel\View\Compile\Php::strip_parentheses($expression));
        $key = 'null';
        $slots = '[]';
        if (preg_match('/,\\s*?key\\(([\\s\\S]*)\\)/', $arguments, $match, PREG_OFFSET_CAPTURE)) {
            $key = trim($match[1][0]);
            $arguments = substr($arguments, 0, $match[0][1]);
        }
        if (preg_match('/,\\s*?(\\$__slots\\s*\\?\\?\\s*\\[\\])/', $arguments, $match, PREG_OFFSET_CAPTURE)) {
            $slots = trim($match[1][0]);
            $arguments = substr($arguments, 0, $match[0][1]);
        }
        $source_key = var_export('lv-'.substr($state->beginDirectiveBlock('liveview'), 0, 16), true);

        return '<?php [$__name, $__params] = (static function ($name, $params = []) { return [$name, $params]; })('.$arguments.'); '
            .'$__key_outer = $__key ?? null; $__key = '.$key.'; $__component_slots = '.$slots.'; '
            .'$__key ??= \\Webkernel\Liveview\\Features\\SupportCompiledWireKeys\\SupportCompiledWireKeys::generateKey('.$source_key.', $__key); '
            .'echo \\Webkernel\Liveview\\Webkernel\\Runtime::mount($__name, $__params, $__key, $__component_slots); '
            .'$__key = $__key_outer; unset($__key_outer, $__name, $__params, $__component_slots); ?>';
    }

    public static function entangle(string $expression): string
    {
        $expression = trim(\Webkernel\View\Compile\Php::strip_parentheses($expression));

        return '<?php $__wire_entangle = ('.$expression.'); '
            .'$__wire_id = $__webkernel_component->getId(); '
            .'if ($__wire_entangle instanceof \\Webkernel\Liveview\\WireDirective) { '
            .'echo "window.Livewire.find(".json_encode($__wire_id).").entangle(".json_encode($__wire_entangle->value()).")".($__wire_entangle->hasModifier("live") ? ".live" : ""); '
            .'} else { echo "window.Livewire.find(".json_encode($__wire_id).").entangle(".json_encode($__wire_entangle).")"; } '
            .'unset($__wire_entangle, $__wire_id); ?>';
    }

    public static function script(string $_expression, State $state): string
    {
        $key = var_export($state->beginDirectiveBlock('script'), true);

        return '<?php $__livewire_script_key = '.$key.'; ob_start(); ?>';
    }

    public static function endscript(string $_expression, State $state): string
    {
        $key = var_export($state->endDirectiveBlock('script'), true);

        return '<?php $__livewire_script_output = (string) ob_get_clean(); '
            .'if (isset($__webkernel_component) && is_object($__webkernel_component)) {'
            .' \\Webkernel\Liveview\\store($__webkernel_component)->push("scripts", $__livewire_script_output, '.$key.');'
            .'} else { echo $__livewire_script_output; }'
            .' unset($__livewire_script_output, $__livewire_script_key); ?>';
    }

    public static function assets(string $_expression, State $state): string
    {
        $key = var_export($state->beginDirectiveBlock('assets'), true);

        return '<?php $__livewire_asset_key = '.$key.'; ob_start(); ?>';
    }

    public static function endassets(string $_expression, State $state): string
    {
        $key = var_export($state->endDirectiveBlock('assets'), true);

        return '<?php $__livewire_asset_output = (string) ob_get_clean(); '
            .'if (! in_array('.$key.', \\Webkernel\Liveview\\Features\\SupportScriptsAndAssets\\SupportScriptsAndAssets::$alreadyRunAssetKeys, true)) {'
            .' \\Webkernel\Liveview\\Features\\SupportScriptsAndAssets\\SupportScriptsAndAssets::$alreadyRunAssetKeys[] = '.$key.';'
            .' if (isset($__webkernel_component) && is_object($__webkernel_component)) {'
            .' \\Webkernel\Liveview\\store($__webkernel_component)->push("assets", $__livewire_asset_output, '.$key.');'
            .' } else {'
            .' \\Webkernel\Liveview\\Features\\SupportScriptsAndAssets\\SupportScriptsAndAssets::$nonLivewireAssets['.$key.'] = $__livewire_asset_output;'
            .' }'
            .'} unset($__livewire_asset_output, $__livewire_asset_key); ?>';
    }

    public static function persist(string $expression): string
    {
        return '<?php \\Webkernel\Liveview\\Webkernel\\Runtime::resolve(\\Webkernel\Liveview\\LivewireManager::class)->forceAssetInjection(); ?>'
            .'<div x-persist="<?php echo \\Webkernel\\View\\View::echo('.$expression.'); ?>">';
    }

    public static function endpersist(string $_expression): string
    {
        return '</div>';
    }

    public static function teleport(string $expression): string
    {
        return '<template x-teleport="<?php echo \\Webkernel\\View\\View::echo('.$expression.'); ?>">';
    }

    public static function endteleport(string $_expression): string
    {
        return '</template>';
    }
}
