<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Cryptography;

use Webkernel\Config\Config;
use Webkernel\Cryptography\Hashing\BcryptHasher;

final class ConfiguredHasher extends BcryptHasher
{
    public function __construct() { parent::__construct((array) Config::get('hashing.bcrypt', [])); }
}
