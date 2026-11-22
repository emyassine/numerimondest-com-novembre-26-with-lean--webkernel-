<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

/*
 * This file is part of the league/config package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webkernel\Markdown\Configuration;

use \Webkernel\Markdown\Configuration\Exception\UnknownOptionException;
use \Webkernel\Markdown\Configuration\Exception\ValidationException;

/**
 * Interface for reading configuration values
 */
interface ConfigurationInterface
{
    /**
     * @param string $key Configuration option path/key
     *
     * @psalm-param non-empty-string $key
     *
     * @return mixed
     *
     * @throws ValidationException if the schema failed to validate the given input
     * @throws UnknownOptionException if the requested key does not exist or is malformed
     */
    public function get(string $key);

    /**
     * @param string $key Configuration option path/key
     *
     * @psalm-param non-empty-string $key
     *
     * @return bool Whether the given option exists
     *
     * @throws ValidationException if the schema failed to validate the given input
     */
    public function exists(string $key): bool;
}
