<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Common\Entity\Comment;

/**
 * This class defines a comment that can be added to a cell.
 */
final class Comment
{
    /** Comment height (CSS style, i.e. XXpx or YYpt). */
    public string $height = '55.5pt';

    /** Comment width (CSS style, i.e. XXpx or YYpt). */
    public string $width = '96pt';

    /** Left margin (CSS style, i.e. XXpx or YYpt). */
    public string $marginLeft = '59.25pt';

    /** Top margin (CSS style, i.e. XXpx or YYpt). */
    public string $marginTop = '1.5pt';

    /** Visible. */
    public bool $visible = false;

    /** Comment fill color. */
    public string $fillColor = '#FFFFE1';

    /** @var TextRun[] */
    private array $textRuns = [];

    public function addTextRun(?TextRun $textRun): void
    {
        $this->textRuns[] = $textRun;
    }

    /**
     * The TextRuns for this comment.
     *
     * @return TextRun[]
     */
    public function getTextRuns(): array
    {
        return $this->textRuns;
    }
}
