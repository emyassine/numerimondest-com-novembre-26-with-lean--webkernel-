<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Writer\Common\Helper;

use Webkernel\Exporters\Xlsx\Common\Exception\IOException;
use Webkernel\Exporters\Xlsx\Common\Helper\FileSystemHelperInterface;

/**
 * @internal
 */
interface FileSystemWithRootFolderHelperInterface extends FileSystemHelperInterface
{
    /**
     * Creates all the folders needed to create a spreadsheet, as well as the files that won't change.
     *
     * @throws IOException If unable to create at least one of the base folders
     */
    public function createBaseFilesAndFolders(): void;

    public function getRootFolder(): string;

    public function getSheetsContentTempFolder(): string;
}
