<?php

namespace Webkernel\Platform\Schemas\Components;

use Webkernel\Platform\Schemas\Components\Concerns\EntanglesStateWithSingularRelationship;
use Webkernel\Platform\Schemas\Components\Contracts\CanEntangleWithSingularRelationships;
use Webkernel\Platform\Support\Components\Contracts\HasEmbeddedView;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;

class Grid extends Component implements CanEntangleWithSingularRelationships, HasEmbeddedView
{
    use EntanglesStateWithSingularRelationship;

    protected ?string $publishedViewOverrideCheckPath = 'webkernel-schemas::components.grid';

    /**
     * @param  array<string, ?int> | int | null  $columns
     */
    final public function __construct(array | int | null $columns)
    {
        $this->columns($columns);
    }

    /**
     * @param  array<string, ?int> | int | null  $columns
     */
    public static function make(array | int | null $columns = 2): static
    {
        $static = app(static::class, ['columns' => $columns]);
        $static->configure();

        return $static;
    }

    public function toEmbeddedHtml(): string
    {
        $attributes = (new PlatformComponentAttributeBag)
            ->merge(['id' => $this->getId()], escape: false)
            ->merge($this->getExtraAttributes(), escape: false);

        $childSchema = $this->getChildSchema();

        ob_start(); ?>

        <div <?= $attributes->toHtml() ?>>
            <?= $childSchema->toHtml() ?>
        </div>

        <?php return ob_get_clean();
    }
}
