<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from Dan Harrin's date-format-converter; see LICENSE-DATE-FORMAT-CONVERTER.md.

namespace Webkernel\Utilities\DateFormat;

use InvalidArgumentException;

final class Converter
{
    private const FORMATS = [
    'A' => [
        'day.js' => 'A',
        'moment.js' => 'A',
    ],
    'a' => [
        'day.js' => 'a',
        'moment.js' => 'a',
    ],
    'B' => [
        'day.js' => '',
        'moment.js' => '',
    ],
    'c' => [
        'day.js' => '',
        'moment.js' => '',
    ],
    'D' => [
        'day.js' => 'ddd',
        'moment.js' => 'ddd',
    ],
    'd' => [
        'day.js' => 'DD',
        'moment.js' => 'DD',
    ],
    'e' => [
        'day.js' => '',
        'moment.js' => '',
    ],
    'F' => [
        'day.js' => 'MMMM',
        'moment.js' => 'MMMM',
    ],
    'G' => [
        'day.js' => 'H',
        'moment.js' => 'H',
    ],
    'g' => [
        'day.js' => 'h',
        'moment.js' => 'h',
    ],
    'H' => [
        'day.js' => 'HH',
        'moment.js' => 'HH',
    ],
    'h' => [
        'day.js' => 'hh',
        'moment.js' => 'hh',
    ],
    'i' => [
        'day.js' => 'mm',
        'moment.js' => 'mm',
    ],
    'I' => [
        'day.js' => '',
        'moment.js' => '',
    ],
    'j' => [
        'day.js' => 'D',
        'moment.js' => 'D',
    ],
    'L' => [
        'day.js' => '',
        'moment.js' => '',
    ],
    'l' => [
        'day.js' => 'dddd',
        'moment.js' => 'dddd',
    ],
    'M' => [
        'day.js' => 'MMM',
        'moment.js' => 'MMM',
    ],
    'm' => [
        'day.js' => 'MM',
        'moment.js' => 'MM',
    ],
    'N' => [
        'day.js' => '',
        'moment.js' => 'E',
    ],
    'n' => [
        'day.js' => 'M',
        'moment.js' => 'M',
    ],
    'O' => [
        'day.js' => 'ZZ',
        'moment.js' => 'ZZ',
    ],
    'o' => [
        'day.js' => '',
        'moment.js' => '',
    ],
    'P' => [
        'day.js' => 'Z',
        'moment.js' => 'Z',
    ],
    'p' => [
        'day.js' => '',
        'moment.js' => '',
    ],
    'r' => [
        'day.js' => '',
        'moment.js' => '',
    ],
    'S' => [
        'day.js' => 'o',
        'moment.js' => 'o',
    ],
    's' => [
        'day.js' => 'ss',
        'moment.js' => 'ss',
    ],
    'T' => [
        'day.js' => '',
        'moment.js' => '',
    ],
    't' => [
        'day.js' => '',
        'moment.js' => '',
    ],
    'U' => [
        'day.js' => '',
        'moment.js' => '',
    ],
    'u' => [
        'day.js' => '',
        'moment.js' => '',
    ],
    'v' => [
        'day.js' => 'SSS',
        'moment.js' => 'SSS',
    ],
    'w' => [
        'day.js' => '',
        'moment.js' => 'e',
    ],
    'Y' => [
        'day.js' => 'YYYY',
        'moment.js' => 'YYYY',
    ],
    'y' => [
        'day.js' => 'YY',
        'moment.js' => 'YY',
    ],
    'X' => [
        'day.js' => '',
        'moment.js' => 'DDDD',
    ],
];

    public function __construct(private readonly string $format)
    {
    }

    public function to(string $standard): string
    {
        if (! in_array($standard, ['day.js', 'moment.js'], true)) {
            throw new InvalidArgumentException("Unsupported date-format standard: {$standard}");
        }

        $result = '';
        $escaped = false;

        foreach (str_split($this->format) as $token) {
            if ($token === '[') {
                $escaped = true;
            }

            if ($escaped) {
                if ($token === ']') {
                    $escaped = false;
                }

                $result .= $token;
                continue;
            }

            $result .= self::FORMATS[$token][$standard] ?? $token;
        }

        return $result;
    }
}
