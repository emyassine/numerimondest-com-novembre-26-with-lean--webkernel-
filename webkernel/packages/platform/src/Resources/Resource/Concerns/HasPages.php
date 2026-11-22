<?php

namespace Webkernel\Platform\Resources\Resource\Concerns;

use Webkernel\Platform\Resources\Pages\PageRegistration;

trait HasPages
{
    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [];
    }

    public static function hasPage(string $page): bool
    {
        return array_key_exists($page, static::getPages());
    }
}
