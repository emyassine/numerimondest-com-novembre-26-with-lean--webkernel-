<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Writer\XLSX;

use Webkernel\Exporters\Xlsx\Common\Helper\Escaper\XLSX;
use Webkernel\Exporters\Xlsx\Common\Helper\StringHelper;
use Webkernel\Exporters\Xlsx\Writer\AbstractWriterMultiSheets;
use Webkernel\Exporters\Xlsx\Writer\Common\Entity\Workbook;
use Webkernel\Exporters\Xlsx\Writer\Common\Helper\ZipHelper;
use Webkernel\Exporters\Xlsx\Writer\Common\Manager\Style\StyleMerger;
use Webkernel\Exporters\Xlsx\Writer\XLSX\Helper\FileSystemHelper;
use Webkernel\Exporters\Xlsx\Writer\XLSX\Manager\CommentsManager;
use Webkernel\Exporters\Xlsx\Writer\XLSX\Manager\SharedStringsManager;
use Webkernel\Exporters\Xlsx\Writer\XLSX\Manager\Style\StyleManager;
use Webkernel\Exporters\Xlsx\Writer\XLSX\Manager\Style\StyleRegistry;
use Webkernel\Exporters\Xlsx\Writer\XLSX\Manager\WorkbookManager;
use Webkernel\Exporters\Xlsx\Writer\XLSX\Manager\WorksheetManager;

final class Writer extends AbstractWriterMultiSheets
{
    /** @var string Content-Type value for the header */
    protected static string $headerContentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    private readonly Options $options;

    public function __construct(?Options $options = null)
    {
        $this->options = $options ?? new Options();
    }

    public function getOptions(): Options
    {
        return $this->options;
    }

    public function setCreator(string $creator): void
    {
        $props = $this->options->getProperties();
        $this->options->setProperties(new Properties(
            $props->title,
            $props->subject,
            $props->application,
            $creator,
            $props->lastModifiedBy,
            $props->keywords,
            $props->description,
            $props->category,
            $props->language,
            $props->customProperties
        ));
    }

    protected function createWorkbookManager(): WorkbookManager
    {
        $workbook = new Workbook();

        $fileSystemHelper = new FileSystemHelper(
            $this->options->getTempFolder(),
            new ZipHelper(),
            new XLSX(),
            $this->options->getProperties()
        );
        $fileSystemHelper->createBaseFilesAndFolders();

        $xlFolder = $fileSystemHelper->getXlFolder();
        $sharedStringsManager = new SharedStringsManager($xlFolder, new XLSX());

        $styleMerger = new StyleMerger();
        $escaper = new XLSX();

        $styleManager = new StyleManager(
            new StyleRegistry($this->options->DEFAULT_ROW_STYLE),
            $escaper
        );

        $commentsManager = new CommentsManager($xlFolder, new XLSX());

        $worksheetManager = new WorksheetManager(
            $this->options,
            $styleManager,
            $styleMerger,
            $commentsManager,
            $sharedStringsManager,
            $escaper,
            StringHelper::factory()
        );

        return new WorkbookManager(
            $workbook,
            $this->options,
            $worksheetManager,
            $styleManager,
            $styleMerger,
            $fileSystemHelper
        );
    }
}
