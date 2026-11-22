<?php

namespace Webkernel\Platform\Schemas\Components\Concerns;

use Webkernel\Platform\Schemas\Contracts\HasSchemas;
use Webkernel\Platform\Schemas\Schema;
use Webkernel\Liveview\Component;

trait BelongsToContainer
{
    protected Schema $container;

    protected Schema $rootContainer;

    public function container(Schema $schema): static
    {
        if (! isset($this->container)) {
            $this->container = $schema;

            return $this;
        }

        if ($this->container === $schema) {
            return $this;
        }

        $this->container = $schema;
        $this->flushCachedHierarchy();

        return $this;
    }

    /**
     * @internal Do not use this method outside the internals of Platform. It is subject to breaking changes in minor and patch releases.
     */
    public function flushCachedHierarchy(): void
    {
        $this->flushCachedAbsoluteKey();
        $this->flushCachedAbsoluteInheritanceKey();
        $this->flushCachedAbsoluteStatePath();
        $this->flushCachedParentRepeaterItem();
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->rootContainer);

        $this->flushCachedChildSchemaHierarchies();
    }

    public function getContainer(): Schema
    {
        return $this->container;
    }

    /**
     * @internal Do not use this method outside the internals of Platform. It is subject to breaking changes in minor and patch releases.
     */
    public function hasContainer(): bool
    {
        return isset($this->container);
    }

    public function getRootContainer(): Schema
    {
        return $this->rootContainer ??= (function (): Schema {
            $container = $this->getContainer();

            while (($parentComponent = $container->getParentComponent()) !== null) {
                $container = $parentComponent->getContainer();
            }

            return $container;
        })();
    }

    /**
     * @internal Do not use this method outside the internals of Platform. It is subject to breaking changes in minor and patch releases.
     */
    public function getModelRootContainer(): Schema
    {
        $container = $this->getContainer();
        $model = $this->getModel();
        $record = $this->getRecord();

        while (($parentComponent = $container->getParentComponent()) !== null) {
            if (
                ($parentComponent->getModel() !== $model) ||
                ($parentComponent->getRecord() !== $record)
            ) {
                break;
            }

            $container = $parentComponent->getContainer();
        }

        return $container;
    }

    public function getLivewire(): Component & HasSchemas
    {
        return $this->getContainer()->getLivewire();
    }
}
