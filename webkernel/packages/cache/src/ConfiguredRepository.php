<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Cache;

use Webkernel\Cache\CacheManager;
use Webkernel\Cache\Repository;
use Webkernel\Container\Container;

final class ConfiguredRepository extends Repository
{
    public function __construct()
    {
        $manager = Container::getInstance()->make(CacheManager::class);
        parent::__construct($manager->store()->getStore());
    }
}
