<?php

namespace Webkernel\Platform\Schemas\Components;

use Closure;
use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Schemas\Components\Concerns\EntanglesStateWithSingularRelationship;
use Webkernel\Platform\Schemas\Components\Contracts\CanEntangleWithSingularRelationships;
use Webkernel\Platform\Support\Components\Contracts\HasEmbeddedView;
use Webkernel\Platform\Support\View\ComponentAttributeBag;
use Webkernel\Platform\Support\View\ComponentAttributeBag as PlatformComponentAttributeBag;
use Webkernel\View\Htmlable;

class Group extends Component implements CanEntangleWithSingularRelationships, HasEmbeddedView
{
    use EntanglesStateWithSingularRelationship;

    protected ?string $publishedViewOverrideCheckPath = 'webkernel-schemas::components.grid';

    /**
     * @param  array<Component | Action | ActionGroup | string | Htmlable> | Closure  $schema
     */
    final public function __construct(array | Closure $schema = [])
    {
        $this->schema($schema);
    }

    /**
     * @param  array<Component | Action | ActionGroup | string | Htmlable> | Closure  $schema
     */
    public static function make(array | Closure $schema = []): static
    {
        $static = app(static::class, ['schema' => $schema]);
        $static->configure();

        return $static;
    }

    public function toEmbeddedHtml(): string
    {
        $attributes = (new PlatformComponentAttributeBag)
            ->merge(['id' => $this->getId()], escape: false)
            ->merge($this->getExtraAttributes(), escape: false);

        ob_start(); ?>

        <div <?= $attributes->toHtml() ?>>
            <?= $this->getChildSchema()->toHtml() ?>
        </div>

        <?php return ob_get_clean();
    }
}
