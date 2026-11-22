<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\QrCode;

use InvalidArgumentException;
use Webkernel\QrCode\Common\MaskPattern;
use Webkernel\QrCode\Common\Mode;
use Webkernel\QrCode\Data\QRData;
use Webkernel\QrCode\Data\QRMatrix;
use Webkernel\QrCode\Options;

final class Generator
{
    public function __construct(private readonly Options $options = new Options())
    {
    }

    public function matrix(string $data): QRMatrix
    {
        if ($data === '') {
            throw new InvalidArgumentException('A QR code requires non-empty data.');
        }

        $segments = [];
        foreach (Mode::INTERFACES as $mode) {
            if ($mode::validateString($data)) {
                $segments[] = new $mode($data);
                break;
            }
        }

        $matrix = (new QRData($this->options, $segments))->writeMatrix();
        $mask = $this->options->mask_pattern === MaskPattern::AUTO
            ? MaskPattern::getBestPattern($matrix)
            : new MaskPattern($this->options->mask_pattern);
        $matrix->setFormatInfo($mask)->mask($mask);
        $matrix->setQuietZone($this->options->quiet_zone);

        return $matrix;
    }

    public function svg(string $data, int $size = 200): string
    {
        if ($size < 1 || $size > 16384) {
            throw new InvalidArgumentException('The SVG size must be between 1 and 16384 pixels.');
        }

        $matrix = $this->matrix($data);
        $dimension = $matrix->getSize();
        $paths = [];
        foreach ($matrix->getMatrix(true) as $y => $row) {
            $start = null;
            for ($x = 0; $x <= $dimension; $x++) {
                if ($x < $dimension && $row[$x]) {
                    $start ??= $x;
                } elseif ($start !== null) {
                    $length = $x - $start;
                    $paths[] = "M{$start} {$y}h{$length}v1h-{$length}z";
                    $start = null;
                }
            }
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 %2$d %2$d" shape-rendering="crispEdges"><path fill="#fff" d="M0 0h%2$dv%2$dH0z"/><path fill="#000" d="%3$s"/></svg>',
            $size,
            $dimension,
            implode('', $paths),
        );
    }

    public function dataUri(string $data, int $size = 200): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($this->svg($data, $size));
    }
}
