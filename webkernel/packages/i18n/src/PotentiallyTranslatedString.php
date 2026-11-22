<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\I18n;

use Stringable;

class PotentiallyTranslatedString implements Stringable
{
    /**
     * The string that may be translated.
     *
     * @var string
     */
    protected $string;

    /**
     * The translated string.
     *
     * @var string|null
     */
    protected $translation;

    /**
     * The validator that may perform the translation.
     *
     * @var \Webkernel\I18n\Contracts\Translator
     */
    protected $translator;

    /**
     * Create a new potentially translated string.
     *
     * @param  string  $string
     * @param  \Webkernel\I18n\Contracts\Translator  $translator
     */
    public function __construct($string, $translator)
    {
        $this->string = $string;

        $this->translator = $translator;
    }

    /**
     * Translate the string.
     *
     * @param  array  $replace
     * @param  string|null  $locale
     * @return $this
     */
    public function translate($replace = [], $locale = null)
    {
        $this->translation = $this->translator->get($this->string, $replace, $locale);

        return $this;
    }

    /**
     * Translates the string based on a count.
     *
     * @param  \Countable|int|float|array  $number
     * @param  array  $replace
     * @param  string|null  $locale
     * @return $this
     */
    public function translateChoice($number, array $replace = [], $locale = null)
    {
        $this->translation = $this->translator->choice($this->string, $number, $replace, $locale);

        return $this;
    }

    /**
     * Get the original string.
     *
     * @return string
     */
    public function original()
    {
        return $this->string;
    }

    /**
     * Get the potentially translated string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->translation ?? $this->string;
    }

    /**
     * Get the potentially translated string.
     *
     * @return string
     */
    public function toString()
    {
        return (string) $this;
    }
}
