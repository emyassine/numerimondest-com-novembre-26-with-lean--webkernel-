<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

if (! function_exists('icon')) {
    /**
     * SVG markup for an icon basename (without .svg).
     */
    function icon(string $name, string $class = '', string $style = '', string $set = 'lucide', ?int $width = null, ?int $height = null, array $attributes = []): string
    {
        return \Webkernel\Imagery\Icon::render($name, $class, $style, $set, $width, $height, $attributes);
    }
}
