<?php

namespace Webkernel\Liveview;

use Webkernel\Liveview\Features\SupportValidation\HandlesValidation;
use Webkernel\Liveview\Features\SupportTransitions\HandlesTransitions;
use Webkernel\Liveview\Features\SupportStreaming\HandlesStreaming;
use Webkernel\Liveview\Features\SupportSlots\HandlesSlots;
use Webkernel\Liveview\Features\SupportReleaseTokens\HandlesReleaseTokens;
use Webkernel\Liveview\Features\SupportRedirects\HandlesRedirects;
use Webkernel\Liveview\Features\SupportPageComponents\HandlesPageComponents;
use Webkernel\Liveview\Features\SupportJsEvaluation\HandlesJsEvaluation;
use Webkernel\Liveview\Features\SupportIslands\HandlesIslands;
use Webkernel\Liveview\Features\SupportFormObjects\HandlesFormObjects;
use Webkernel\Liveview\Features\SupportEvents\HandlesEvents;
use Webkernel\Liveview\Features\SupportHtmlAttributeForwarding\HandlesHtmlAttributeForwarding;
use Webkernel\Liveview\Features\SupportDisablingBackButtonCache\HandlesDisablingBackButtonCache;
use Webkernel\Liveview\Features\SupportAttributes\HandlesAttributes;
use Webkernel\Liveview\Features\SupportRenderless\HandlesRenderless;
use Webkernel\Liveview\Exceptions\PropertyNotFoundException;
use Webkernel\Liveview\Concerns\InteractsWithProperties;
use Webkernel\Utilities\Traits\Macroable;
use \Webkernel\Accounts\Concerns\AuthorizesRequests;
use BadMethodCallException;

abstract class Component
{
    use Macroable { __call as macroCall; }

    use AuthorizesRequests;
    use InteractsWithProperties;
    use HandlesEvents;
    use HandlesIslands;
    use HandlesRedirects;
    use HandlesTransitions;
    use HandlesStreaming;
    use HandlesAttributes;
    use HandlesValidation;
    use HandlesFormObjects;
    use HandlesJsEvaluation;
    use HandlesReleaseTokens;
    use HandlesPageComponents;
    use HandlesDisablingBackButtonCache;
    use HandlesSlots;
    use HandlesHtmlAttributeForwarding;
    use HandlesRenderless;

    protected $__id;
    protected $__name;

    function id()
    {
        return $this->getId();
    }

    function setId($id)
    {
        $this->__id = $id;
    }

    function getId()
    {
        return $this->__id;
    }

    function setName($name)
    {
        $this->__name = $name;
    }

    function getName()
    {
        return $this->__name;
    }

    function forceRender()
    {
        store($this)->set('forceRender', true);
    }

    function skipMount()
    {
        store($this)->set('skipMount', true);
    }

    function skipHydrate()
    {
        store($this)->set('skipHydrate', true);
    }

    function hasProvidedView()
    {
        return method_exists($this, 'view');
    }

    function getProvidedView()
    {
        return $this->view();
    }

    function __isset($property)
    {
        try {
            $value = $this->__get($property);

            if (isset($value)) {
                return true;
            }
        } catch(PropertyNotFoundException $ex) {}

        return false;
    }

    function __get($property)
    {
        $value = 'noneset';

        $returnValue = function ($newValue) use (&$value) {
            $value = $newValue;
        };

        $finish = trigger('__get', $this, $property, $returnValue);

        $value = $finish($value);

        if ($value === 'noneset') {
            throw new PropertyNotFoundException($property, $this->getName());
        }

        return $value;
    }

    function __unset($property)
    {
        trigger('__unset', $this, $property);
    }

    function __call($method, $params)
    {
        $value = 'noneset';

        $returnValue = function ($newValue) use (&$value) {
            $value = $newValue;
        };

        $finish = trigger('__call', $this, $method, $params, $returnValue);

        $value = $finish($value);

        if ($value !== 'noneset') {
            return $value;
        }

        if (static::hasMacro($method)) {
            return $this->macroCall($method, $params);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }

    public function tap($callback)
    {
        $callback($this);

        return $this;
    }
}
