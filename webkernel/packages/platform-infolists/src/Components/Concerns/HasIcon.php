<?php

namespace Webkernel\Platform\Infolists\Components\Concerns;

use BackedEnum;
use Closure;
use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\Platform\Support\Contracts\HasIcon as IconInterface;
use Webkernel\Platform\Support\Enums\IconPosition;
use Webkernel\View\Htmlable;
use Webkernel\ORM\Model;

trait HasIcon
{
    protected string | BackedEnum | Htmlable | bool | Closure | null $icon = null;

    protected IconPosition | string | Closure | null $iconPosition = null;

    public function icon(string | BackedEnum | Htmlable | bool | Closure | null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @param  array<mixed> | Closure  $icons
     */
    public function icons(array | Closure $icons): static
    {
        $this->icon(function (Component $component, $state, ?Model $relatedRecord) use ($icons) {
            $icons = $component->evaluate($icons, [
                'state' => $state,
                'relatedRecord' => $relatedRecord,
            ]);

            $icon = null;

            foreach ($icons as $conditionalIcon => $condition) {
                if (is_numeric($conditionalIcon)) {
                    $icon = $condition;
                } elseif ($condition instanceof Closure && $component->evaluate($condition, [
                    'state' => $state,
                    'relatedRecord' => $relatedRecord,
                ])) {
                    $icon = $conditionalIcon;
                } elseif ($condition === $state) {
                    $icon = $conditionalIcon;
                }
            }

            return $icon;
        });

        return $this;
    }

    public function iconPosition(IconPosition | string | Closure | null $iconPosition): static
    {
        $this->iconPosition = $iconPosition;

        return $this;
    }

    public function getIcon(mixed $state, ?Model $relatedRecord = null): string | BackedEnum | Htmlable | null
    {
        $icon = $this->evaluate($this->icon, [
            'state' => $state,
            'relatedRecord' => $relatedRecord,
        ]);

        if ($icon === false) {
            return null;
        }

        if (filled($icon)) {
            return $icon;
        }

        if (! $state instanceof IconInterface) {
            return null;
        }

        return $state->getIcon();
    }

    public function getIconPosition(): IconPosition | string
    {
        $position = $this->evaluate($this->iconPosition);

        if ($position instanceof IconPosition) {
            return $position;
        }

        if (blank($position)) {
            return IconPosition::Before;
        }

        return IconPosition::tryFrom($position) ?? $position;
    }
}
