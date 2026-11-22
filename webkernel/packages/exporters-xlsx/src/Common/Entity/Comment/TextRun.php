<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from OpenSpout, XLSX writer components; see ../LICENSE.
namespace Webkernel\Exporters\Xlsx\Common\Entity\Comment;

/**
 * This class defines rich text in a fluent interface that can be added to a comment.
 */
final class TextRun
{
    public string $text;
    public int $fontSize = 10;
    public string $fontColor = '000000';
    public string $fontName = 'Tahoma';
    public bool $bold = false;
    public bool $italic = false;

    public function __construct(string $text)
    {
        $this->text = $text;
    }
}
