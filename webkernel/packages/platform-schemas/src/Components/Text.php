<?php

namespace Webkernel\Platform\Schemas\Components;

use BackedEnum;
use Closure;
use Webkernel\Platform\Schemas\JsContent;
use Webkernel\Platform\Schemas\View\Components\TextComponent;
use Webkernel\Platform\Support\Components\Contracts\HasEmbeddedView;
use Webkernel\Platform\Support\Concerns\CanBeCopied;
use Webkernel\Platform\Support\Concerns\HasColor;
use Webkernel\Platform\Support\Concerns\HasFontFamily;
use Webkernel\Platform\Support\Concerns\HasIcon;
use Webkernel\Platform\Support\Concerns\HasIconPosition;
use Webkernel\Platform\Support\Concerns\HasIconSize;
use Webkernel\Platform\Support\Concerns\HasTooltip;
use Webkernel\Platform\Support\Concerns\HasWeight;
use Webkernel\Platform\Support\Enums\FontFamily;
use Webkernel\Platform\Support\Enums\FontWeight;
use Webkernel\Platform\Support\Enums\IconPosition;
use Webkernel\Platform\Support\Enums\IconSize;
use Webkernel\Platform\Support\Enums\Size;
use Webkernel\Platform\Support\Enums\TextSize;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\Platform\Support\View\Components\BadgeComponent;
use Webkernel\View\Htmlable;
use Webkernel\View\Js;

use function Webkernel\Platform\Support\generate_icon_html;

class Text extends Component implements HasEmbeddedView
{
    use CanBeCopied;
    use HasColor;
    use HasFontFamily;
    use HasIcon;
    use HasIconPosition;
    use HasIconSize;
    use HasTooltip;
    use HasWeight;

    protected ?string $publishedViewOverrideCheckPath = 'webkernel-schemas::components.text';

    protected string | Htmlable | Closure | null $content;

    protected bool | Closure $isBadge = false;

    protected TextSize | Size | string | Closure | null $size = null;

    final public function __construct(string | Htmlable | Closure | null $content)
    {
        $this->content($content);
    }

    public static function make(string | Htmlable | Closure | null $content): static
    {
        $static = app(static::class, ['content' => $content]);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultColor('gray');
    }

    public function content(string | Htmlable | Closure | null $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function badge(bool | Closure $condition = true): static
    {
        $this->isBadge = $condition;

        return $this;
    }

    public function isBadge(): bool
    {
        return (bool) $this->evaluate($this->isBadge);
    }

    public function js(): static
    {
        $this->content(JsContent::make($this->getContent()));

        return $this;
    }

    public function getContent(): string | Htmlable | null
    {
        return $this->evaluate($this->content);
    }

    public function size(TextSize | Size | string | Closure | null $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): TextSize | Size | string | null
    {
        $size = $this->evaluate($this->size);

        if (blank($size)) {
            return null;
        }

        if (is_string($size)) {
            $size = TextSize::tryFrom($size) ?? $size;
        }

        return $size;
    }

    public function toEmbeddedHtml(): string
    {
        $color = $this->getColor();
        $content = $this->getContent();
        $icon = $this->getIcon();
        $iconPosition = $this->getIconPosition();
        $iconSize = $this->getIconSize();
        $size = $this->getSize();
        $tooltip = $this->getTooltip();
        $weight = $this->getWeight();
        $fontFamily = $this->getFontFamily();

        $copyableState = $this->getCopyableState($content) ?? $content;
        $copyMessage = $this->getCopyMessage($copyableState);
        $copyMessageDuration = $this->getCopyMessageDuration($copyableState);
        $isCopyable = $this->isCopyable($copyableState);

        $hasTooltip = filled($tooltip);

        if ($this->isBadge()) {
            if (! $size instanceof Size) {
                $size = filled($size) ? (is_string($size) ? (Size::tryFrom($size) ?? $size) : $size) : Size::Medium;
            }

            if (filled($iconSize) && (! $iconSize instanceof IconSize)) {
                $iconSize = IconSize::tryFrom($iconSize) ?? $iconSize;
            }

            $badgeAttributes = $this->getExtraAttributeBag()
                ->merge([
                    'type' => $isCopyable ? 'button' : null,
                ], escape: false)
                ->class([
                    'fi-sc-text',
                    'fi-badge',
                    ($size instanceof BackedEnum) ? "fi-size-{$size->value}" : $size,
                ])
                ->color(BadgeComponent::class, $color ?? 'primary');

            ob_start(); ?>

            <<?= $isCopyable ? 'button' : 'span' ?>
                <?php if ($isCopyable) { ?>
                    x-on:click="
                        window.navigator.clipboard.writeText(<?= Js::from($copyableState) ?>)
                        $tooltip(<?= Js::from($copyMessage) ?>, {
                            theme: $store.theme,
                            timeout: <?= Js::from($copyMessageDuration) ?>,
                        })
                    "
                <?php } ?>
                <?php if ($hasTooltip) { ?>
                    x-tooltip="{
                        content: <?= Js::from($tooltip) ?>,
                        theme: $store.theme,
                        allowHTML: <?= Js::from($tooltip instanceof Htmlable) ?>,
                    }"
                <?php } ?>
                <?= $badgeAttributes->toHtml() ?>
            >
                <?php if ($iconPosition === IconPosition::Before && filled($icon)) { ?>
                    <?= generate_icon_html($icon, size: $iconSize ?? IconSize::Small)?->toHtml() ?>
                <?php } ?>

                <span class="fi-badge-label-ctn">
                    <span class="fi-badge-label"><?= e($content) ?></span>
                </span>

                <?php if ($iconPosition === IconPosition::After && filled($icon)) { ?>
                    <?= generate_icon_html($icon, size: $iconSize ?? IconSize::Small)?->toHtml() ?>
                <?php } ?>
            </<?= $isCopyable ? 'button' : 'span' ?>>

            <?php return ob_get_clean();
        }

        // Non-badge mode: simple span
        $spanAttributes = (new PlatformComponentAttributeBag)
            ->color(TextComponent::class, $color)
            ->class([
                'fi-sc-text',
                'fi-copyable' => $isCopyable,
                ($size instanceof BackedEnum) ? "fi-size-{$size->value}" : $size,
                ($weight instanceof FontWeight) ? "fi-font-{$weight->value}" : $weight,
                ($fontFamily instanceof FontFamily) ? "fi-font-{$fontFamily->value}" : $fontFamily,
            ])
            ->merge($this->getExtraAttributes(), escape: false);

        ob_start(); ?>

        <span
            <?php if ($isCopyable) { ?>
                role="button"
                tabindex="0"
                x-on:click="
                    window.navigator.clipboard.writeText(<?= Js::from($copyableState) ?>)
                    $tooltip(<?= Js::from($copyMessage) ?>, {
                        theme: $store.theme,
                        timeout: <?= Js::from($copyMessageDuration) ?>,
                    })
                "
                x-on:keydown.enter.prevent="$el.click()"
                x-on:keydown.space.prevent="$el.click()"
            <?php } ?>
            <?php if ($hasTooltip) { ?>
                x-tooltip="{
                    content: <?= Js::from($tooltip) ?>,
                    theme: $store.theme,
                    allowHTML: <?= Js::from($tooltip instanceof Htmlable) ?>,
                }"
            <?php } ?>
            <?= $spanAttributes->toHtml() ?>
        >
            <?= e($content) ?>
        </span>

        <?php return ob_get_clean();
    }
}
