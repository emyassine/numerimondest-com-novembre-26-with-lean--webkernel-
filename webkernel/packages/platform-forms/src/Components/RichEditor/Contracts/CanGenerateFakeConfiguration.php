<?php

namespace Webkernel\Platform\Forms\Components\RichEditor\Contracts;

use Faker\Generator;

interface CanGenerateFakeConfiguration
{
    /**
     * @return array<string, mixed>
     */
    public static function generateFakeConfiguration(Generator $faker): array;
}
