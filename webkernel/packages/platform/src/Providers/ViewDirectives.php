<?php declare(strict_types=1);

namespace Webkernel\Platform\Providers;

/** Native view compiler handlers used by Platform package views. */
final class ViewDirectives
{
    public static function scripts(string $expression): string
    {
        $expression = $expression === '' ? '()' : $expression;

        return '<?php echo \\Webkernel\\Container\\Container::getInstance()->get(\\Webkernel\\Platform\\Support\\Assets\\AssetManager::class)->renderScripts'
            . $expression . '; ?>';
    }

    public static function styles(string $expression): string
    {
        $expression = $expression === '' ? '()' : $expression;

        return '<?php echo \\Webkernel\\Container\\Container::getInstance()->get(\\Webkernel\\Platform\\Support\\Assets\\AssetManager::class)->renderStyles'
            . $expression . '; ?>';
    }

    public static function captureSlots(string $expression): string
    {
        $slots = trim($expression);
        if (str_starts_with($slots, '(') && str_ends_with($slots, ')')) {
            $slots = substr($slots, 1, -1);
        }

        return '<?php $slotContents = get_defined_vars(); $slots = array_intersect_key($slotContents, array_flip(' . $slots . ')); unset($slotContents); ?>';
    }
}
