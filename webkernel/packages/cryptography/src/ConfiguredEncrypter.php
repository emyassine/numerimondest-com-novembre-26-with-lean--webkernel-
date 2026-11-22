<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Cryptography;

use RuntimeException;
use Webkernel\Config\Config;
use Webkernel\Cryptography\Encryption\Encrypter;

final class ConfiguredEncrypter extends Encrypter
{
    public function __construct()
    {
        $key = Config::get('app.key', '');
        if (! is_string($key) || $key === '') { throw new RuntimeException('Configure app.key before using encrypted attributes.'); }
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true);
            if ($key === false) { throw new RuntimeException('The encryption key is not valid base64.'); }
        }
        parent::__construct($key, (string) Config::get('app.cipher', 'aes-256-cbc'));
    }
}
