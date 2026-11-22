<?php

namespace Webkernel\Liveview\Features\SupportDisablingBackButtonCache;

trait HandlesDisablingBackButtonCache
{
    function disableBackButtonCache()
    {
        SupportDisablingBackButtonCache::$disableBackButtonCache = true;
    }

    function enableBackButtonCache()
    {
        SupportDisablingBackButtonCache::$disableBackButtonCache = false;
    }
}
