<?php

namespace Webkernel\Platform\Schemas\Concerns;

use Webkernel\Platform\Actions\Contracts\HasActions;
use Webkernel\Platform\Schemas\Contracts\HasSchemas;
use Webkernel\Liveview\Exceptions\PropertyNotFoundException;

trait ResolvesDynamicLivewireProperties
{
    /**
     * @param  string  $property
     *
     * @throws PropertyNotFoundException
     */
    public function __get($property): mixed
    {
        try {
            return parent::__get($property);
        } catch (PropertyNotFoundException $exception) {
        }

        if (
            $this instanceof HasSchemas &&
            (! $this->isCachingSchemas()) &&
            $schema = $this->getSchema($property)
        ) {
            return $schema;
        }

        if (
            $this instanceof HasActions &&
            $action = $this->getAction($property, isMounting: false)
        ) {
            return $action;
        }

        throw $exception;
    }
}
