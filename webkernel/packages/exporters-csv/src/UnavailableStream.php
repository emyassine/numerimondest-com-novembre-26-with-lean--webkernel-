<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from League.Csv; see ../LICENSE.
/**
 * League.Csv (https://csv.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webkernel\Exporters\Csv;

final class UnavailableStream extends Exception
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function dueToPathNotFound(string $path): self
    {
        return new self('`'.$path.'`: failed to open stream: No such file or directory.');
    }

    public static function dueToForbiddenCloning(string $class_name): self
    {
        return new self('An object of class '.$class_name.' cannot be cloned.');
    }
}
