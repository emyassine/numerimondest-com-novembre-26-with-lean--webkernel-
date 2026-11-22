<?php

namespace Webkernel\Platform\Panel\Concerns;

use Webkernel\Platform\Support\Assets\Asset;
use Webkernel\Platform\Support\Facades\PlatformAsset;

trait HasAssets
{
    /**
     * @var array<string, array<Asset>>
     */
    protected array $assets = [];

    /**
     * @param  array<Asset>  $assets
     */
    public function assets(array $assets, string $package = 'app'): static
    {
        $this->assets[$package] = [
            ...($this->assets[$package] ?? []),
            ...$assets,
        ];

        return $this;
    }

    public function registerAssets(): void
    {
        foreach ($this->assets as $package => $assets) {
            PlatformAsset::register($assets, $package);
        }
    }
}
