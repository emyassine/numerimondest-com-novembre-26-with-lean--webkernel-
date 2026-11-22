<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

use Webkernel\Container\Container;

if (! function_exists('app')) {
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        $container = Container::getInstance();
        return $abstract === null ? $container : $container->make($abstract, $parameters);
    }
}

if (! function_exists('resolve')) {
    function resolve(string $abstract, array $parameters = []): mixed
    {
        return Container::getInstance()->make($abstract, $parameters);
    }
}
