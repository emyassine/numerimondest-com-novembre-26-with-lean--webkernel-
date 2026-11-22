<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Utilities\Inflection;

use \Webkernel\Utilities\Inflection\Rules\English;
use \Webkernel\Utilities\Inflection\Rules\Esperanto;
use \Webkernel\Utilities\Inflection\Rules\French;
use \Webkernel\Utilities\Inflection\Rules\Italian;
use \Webkernel\Utilities\Inflection\Rules\NorwegianBokmal;
use \Webkernel\Utilities\Inflection\Rules\Portuguese;
use \Webkernel\Utilities\Inflection\Rules\Spanish;
use \Webkernel\Utilities\Inflection\Rules\Turkish;
use InvalidArgumentException;

use function sprintf;

final class InflectorFactory
{
    public static function create(): LanguageInflectorFactory
    {
        return self::createForLanguage(Language::ENGLISH);
    }

    public static function createForLanguage(string $language): LanguageInflectorFactory
    {
        switch ($language) {
            case Language::ENGLISH:
                return new English\InflectorFactory();

            case Language::ESPERANTO:
                return new Esperanto\InflectorFactory();

            case Language::FRENCH:
                return new French\InflectorFactory();

            case Language::ITALIAN:
                return new Italian\InflectorFactory();

            case Language::NORWEGIAN_BOKMAL:
                return new NorwegianBokmal\InflectorFactory();

            case Language::PORTUGUESE:
                return new Portuguese\InflectorFactory();

            case Language::SPANISH:
                return new Spanish\InflectorFactory();

            case Language::TURKISH:
                return new Turkish\InflectorFactory();

            default:
                throw new InvalidArgumentException(sprintf(
                    'Language "%s" is not supported.',
                    $language
                ));
        }
    }
}
