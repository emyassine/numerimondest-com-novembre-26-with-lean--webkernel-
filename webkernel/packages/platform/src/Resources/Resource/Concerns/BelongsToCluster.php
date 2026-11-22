<?php

namespace Webkernel\Platform\Resources\Resource\Concerns;

use Webkernel\Platform\Clusters\Cluster;

trait BelongsToCluster
{
    /**
     * @var class-string<Cluster> | null
     */
    protected static ?string $cluster = null;

    /**
     * @return class-string<Cluster> | null
     */
    public static function getCluster(): ?string
    {
        return static::$cluster;
    }
}
