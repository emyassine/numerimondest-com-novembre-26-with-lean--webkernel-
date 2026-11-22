<?php

namespace Webkernel\Liveview\Features\SupportRenderless;

use Webkernel\Liveview\Features\SupportAttributes\AttributeLevel;

use function Webkernel\Liveview\store;

trait HandlesRenderless
{
    public function renderless()
    {
        $this->skipRender();
    }

    public function skipRender($html = null)
    {
        if (store($this)->has('forceRender')) {
            return;
        }

        store($this)->set('skipRender', $html ?: true);
    }

    public function shouldSkipRender()
    {
        return store($this)->get('skipRender', false);
    }

    public function isRenderlessMethod($method)
    {
        return $this->getAttributes()
            ->whereInstanceOf(BaseRenderless::class)
            ->filter(fn ($attribute) => $attribute->getLevel() === AttributeLevel::METHOD)
            ->contains(fn ($attribute) => $attribute->getName() === $method);
    }

}
