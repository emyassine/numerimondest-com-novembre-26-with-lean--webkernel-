<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\I18n\Providers;

use Webkernel\ServiceProvider;

/** Declares the package's translation catalogs. */
final class I18nProvider extends ServiceProvider
{
    public const array LANG_PATH = [__DIR__ . '/../../lang'];

    public const array COMPONENTS = ['webkernel' => [__DIR__ . '/../../resources/views/components']];
}
