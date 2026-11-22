<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Common;

use Webkernel\Exporters\Xlsx\Common\Exception\InvalidArgumentException;

/**
 * @internal
 */
trait TempFolderOptionTrait
{
    private string $tempFolder;

    final public function setTempFolder(string $tempFolder): void
    {
        if (!is_dir($tempFolder) || !is_writable($tempFolder)) {
            throw new InvalidArgumentException("{$tempFolder} is not a writable folder");
        }

        $this->tempFolder = $tempFolder;
    }

    final public function getTempFolder(): string
    {
        if (!isset($this->tempFolder)) {
            $this->setTempFolder(sys_get_temp_dir());
        }

        return $this->tempFolder;
    }
}
