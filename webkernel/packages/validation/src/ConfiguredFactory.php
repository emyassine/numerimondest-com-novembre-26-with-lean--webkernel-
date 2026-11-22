<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Validation;

use Webkernel\Container\Container;
use Webkernel\Filesystem\Filesystem;
use Webkernel\I18n\FileLoader;
use Webkernel\I18n\Translator;
use Webkernel\Validation\Factory;
use Webkernel\Config\Config;

final class ConfiguredFactory extends Factory
{
    public function __construct()
    {
        $loader = new FileLoader(new Filesystem(), __DIR__.'/../lang');
        $translator = new Translator($loader, (string) Config::get('app.locale', 'en'));
        $translator->setFallback('en');
        parent::__construct($translator, Container::getInstance());
    }
}
