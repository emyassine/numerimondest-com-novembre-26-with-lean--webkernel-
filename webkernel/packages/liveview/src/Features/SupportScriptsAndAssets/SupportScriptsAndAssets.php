<?php

namespace Webkernel\Liveview\Features\SupportScriptsAndAssets;

use Webkernel\Liveview\ComponentHook;

use function Webkernel\Liveview\on;

class SupportScriptsAndAssets extends ComponentHook
{
    public static $alreadyRunAssetKeys = [];

    public static $renderedAssets = [];

    public static $nonLivewireAssets = [];

    public static function getAssets()
    {
        return static::$renderedAssets;
    }

    public static function processNonLivewireAssets()
    {
        // If any assets have been added outside of a Livewire component, then they will not be 
        // processed like the other assets as there is no dehydrate being called. So instead 
        // we process them manually that way they are included with the other assets when
        // they are injected...
        $alreadyRunAssetKeys = [];

        foreach (static::$nonLivewireAssets as $key => $assets) {
             if (! in_array($key, $alreadyRunAssetKeys)) {

                // These will get injected into the HTML if it's an initial page load...
                static::$renderedAssets[$key] = $assets;

                $alreadyRunAssetKeys[] = $key;
            }
        }
    }

    static function provide()
    {
        on('flush-state', function () {
            static::$alreadyRunAssetKeys = [];
            static::$renderedAssets = [];
            static::$nonLivewireAssets = [];
        });
    }

    function hydrate($memo) {
        // Store the "scripts" and "assets" memos so they can be re-added later (persisted between requests)...
        if (isset($memo['scripts'])) {
            $this->storeSet('forwardScriptsToDehydrateMemo', $memo['scripts']);
        }

        if (isset($memo['assets'])) {
            $this->storeSet('forwardAssetsToDehydrateMemo', $memo['assets']);
        }
    }

    function dehydrate($context)
    {
        $alreadyRunScriptKeys = $this->storeGet('forwardScriptsToDehydrateMemo', []);

        // Add any scripts to the payload that haven't been run yet for this component....
        foreach ($this->storeGet('scripts', []) as $key => $script) {
            if (! in_array($key, $alreadyRunScriptKeys)) {
                $context->pushEffect('scripts', $script, $key);
                $alreadyRunScriptKeys[] = $key;
            }
        }

        $context->addMemo('scripts', $alreadyRunScriptKeys);

        // Add any assets to the payload that haven't been run yet for the entire page...

        $alreadyRunAssetKeys = $this->storeGet('forwardAssetsToDehydrateMemo', []);

        foreach ($this->storeGet('assets', []) as $key => $assets) {
            if (! in_array($key, $alreadyRunAssetKeys)) {

                // These will either get injected into the HTML if it's an initial page load
                // or they will be added to the "assets" key in an ajax payload...
                static::$renderedAssets[$key] = $assets;

                $alreadyRunAssetKeys[] = $key;
            }
        }

        $context->addMemo('assets', $alreadyRunAssetKeys);
    }
}
