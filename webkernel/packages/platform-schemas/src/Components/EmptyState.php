<?php

namespace Webkernel\Platform\Schemas\Components;

use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Schemas\Components\Concerns\CanBeCompact;
use Webkernel\Platform\Schemas\Components\Concerns\HasDescription;
use Webkernel\Platform\Schemas\Schema;
use Webkernel\Platform\Support\Components\Contracts\HasEmbeddedView;
use Webkernel\Platform\Support\Concerns\CanBeContained;
use Webkernel\Platform\Support\Concerns\HasIcon;
use Webkernel\Platform\Support\Concerns\HasIconColor;
use Webkernel\Platform\Support\Concerns\HasIconSize;
use Webkernel\Platform\Support\Enums\IconSize;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\Platform\Support\View\Components\SectionComponent\IconComponent;
use Webkernel\View\Htmlable;

use function Webkernel\Platform\Support\generate_icon_html;

class EmptyState extends Component implements HasEmbeddedView
{
    use CanBeCompact;
    use CanBeContained;
    use HasDescription;
    use HasIcon;
    use HasIconColor;
    use HasIconSize;

    protected ?string $publishedViewOverrideCheckPath = 'webkernel-schemas::components.empty-state';

    protected string | Htmlable | Closure $heading;

    const FOOTER_SCHEMA_KEY = 'footer';

    final public function __construct(string | Htmlable | Closure $heading)
    {
        $this->heading($heading);
    }

    public static function make(string | Htmlable | Closure $heading): static
    {
        $static = app(static::class, ['heading' => $heading]);
        $static->configure();

        return $static;
    }

    /**
     * @param  array<Component | Action | ActionGroup | string | Htmlable> | Schema | Component | Action | ActionGroup | string | Htmlable | Closure | null  $components
     */
    public function footer(array | Schema | Component | Action | ActionGroup | string | Htmlable | Closure | null $components): static
    {
        $this->childComponents($components, static::FOOTER_SCHEMA_KEY);

        return $this;
    }

    protected function configureChildSchema(Schema $schema, string $key): Schema
    {
        $schema = parent::configureChildSchema($schema, $key);

        if (in_array($key, [
            static::FOOTER_SCHEMA_KEY,
        ])) {
            $schema
                ->inline()
                ->embeddedInParentComponent();
        }

        return $schema;
    }

    public function heading(string | Htmlable | Closure $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function getHeading(): string | Htmlable
    {
        return $this->evaluate($this->heading);
    }

    public function toEmbeddedHtml(): string
    {
        $description = $this->getDescription();
        $footer = $this->getChildSchema(static::FOOTER_SCHEMA_KEY)?->toHtmlString();
        $heading = $this->getHeading();
        $headingTag = $this->getHeadingTag();
        $icon = $this->getIcon();
        $iconColor = $this->getIconColor() ?? 'primary';
        $iconSize = $this->getIconSize();
        $isCompact = $this->isCompact();
        $isContained = $this->isContained();

        if (filled($iconSize) && (! $iconSize instanceof IconSize)) {
            $iconSize = IconSize::tryFrom($iconSize) ?? $iconSize;
        }

        $hasDescription = filled((string) $description);
        $hasFooter = filled((string) $footer);
        $hasIcon = filled($icon);

        $outerAttributes = (new PlatformComponentAttributeBag)
            ->merge($this->getExtraAttributes(), escape: false)
            ->class(['fi-sc-empty-state']);

        $sectionAttributes = (new PlatformComponentAttributeBag)
            ->class([
                'fi-empty-state',
                'fi-compact' => $isCompact,
                'fi-empty-state-not-contained' => ! $isContained,
            ]);

        ob_start(); ?>

        <div <?= $outerAttributes->toHtml() ?>>
            <section <?= $sectionAttributes->toHtml() ?>>
                <div class="fi-empty-state-content">
                    <?php if ($hasIcon) { ?>
                        <div
                            <?= (new PlatformComponentAttributeBag)->class([
                                'fi-empty-state-icon-bg',
                                'fi-color ' . ('fi-color-' . $iconColor) => $iconColor !== 'gray',
                            ])->toHtml() ?>
                        >
                            <?= generate_icon_html($icon, attributes: (new PlatformComponentAttributeBag)
                                ->color(IconComponent::class, $iconColor), size: $iconSize ?? IconSize::Large)?->toHtml() ?>
                        </div>
                    <?php } ?>

                    <div class="fi-empty-state-text-ctn">
                        <<?= $headingTag ?> class="fi-empty-state-heading">
                            <?= e($heading) ?>
                        </<?= $headingTag ?>>

                        <?php if ($hasDescription) { ?>
                            <p class="fi-empty-state-description"><?= e($description) ?></p>
                        <?php } ?>

                        <?php if ($hasFooter) { ?>
                            <footer class="fi-empty-state-footer">
                                <?= $footer->toHtml() ?>
                            </footer>
                        <?php } ?>
                    </div>
                </div>
            </section>
        </div>

        <?php return ob_get_clean();
    }
}
