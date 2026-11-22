<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Writer\XLSX;

use Webkernel\Exporters\Xlsx\Common\Entity\Style\Style;
use Webkernel\Exporters\Xlsx\Writer\Common\AbstractOptions;
use Webkernel\Exporters\Xlsx\Writer\XLSX\Options\HeaderFooter;
use Webkernel\Exporters\Xlsx\Writer\XLSX\Options\PageMargin;
use Webkernel\Exporters\Xlsx\Writer\XLSX\Options\PageSetup;
use Webkernel\Exporters\Xlsx\Writer\XLSX\Options\WorkbookProtection;

final class Options extends AbstractOptions
{
    public const DEFAULT_FONT_SIZE = 12;
    public const DEFAULT_FONT_NAME = 'Calibri';

    public bool $SHOULD_USE_INLINE_STRINGS = true;

    /** @var MergeCell[] */
    private array $MERGE_CELLS = [];

    private ?PageMargin $pageMargin = null;

    private ?PageSetup $pageSetup = null;

    private ?HeaderFooter $headerFooter = null;

    private ?WorkbookProtection $workbookProtection = null;

    private Properties $properties;

    public function __construct()
    {
        parent::__construct();

        $defaultRowStyle = new Style();
        $defaultRowStyle->setFontSize(self::DEFAULT_FONT_SIZE);
        $defaultRowStyle->setFontName(self::DEFAULT_FONT_NAME);

        $this->DEFAULT_ROW_STYLE = $defaultRowStyle;

        $this->properties = new Properties();
    }

    /**
     * Row coordinates are indexed from 1, columns from 0 (A = 0),
     * so a merge B2:G2 looks like $writer->mergeCells(1, 2, 6, 2);.
     *
     * @param 0|positive-int $topLeftColumn
     * @param positive-int   $topLeftRow
     * @param 0|positive-int $bottomRightColumn
     * @param positive-int   $bottomRightRow
     * @param 0|positive-int $sheetIndex
     */
    public function mergeCells(
        int $topLeftColumn,
        int $topLeftRow,
        int $bottomRightColumn,
        int $bottomRightRow,
        int $sheetIndex = 0,
    ): void {
        $this->MERGE_CELLS[] = new MergeCell(
            $sheetIndex,
            $topLeftColumn,
            $topLeftRow,
            $bottomRightColumn,
            $bottomRightRow
        );
    }

    /**
     * @return MergeCell[]
     *
     * @internal
     */
    public function getMergeCells(): array
    {
        return $this->MERGE_CELLS;
    }

    public function setPageMargin(PageMargin $pageMargin): void
    {
        $this->pageMargin = $pageMargin;
    }

    public function getPageMargin(): ?PageMargin
    {
        return $this->pageMargin;
    }

    public function setPageSetup(PageSetup $pageSetup): void
    {
        $this->pageSetup = $pageSetup;
    }

    public function getPageSetup(): ?PageSetup
    {
        return $this->pageSetup;
    }

    public function setHeaderFooter(HeaderFooter $headerFooter): void
    {
        $this->headerFooter = $headerFooter;
    }

    public function getHeaderFooter(): ?HeaderFooter
    {
        return $this->headerFooter;
    }

    public function getWorkbookProtection(): ?WorkbookProtection
    {
        return $this->workbookProtection;
    }

    public function setWorkbookProtection(WorkbookProtection $workbookProtection): void
    {
        $this->workbookProtection = $workbookProtection;
    }

    public function getProperties(): Properties
    {
        return $this->properties;
    }

    public function setProperties(Properties $properties): void
    {
        $this->properties = $properties;
    }
}
