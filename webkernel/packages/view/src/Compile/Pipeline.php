<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View\Compile;

use Webkernel\View\Markdown;

/**
 * Token walk: PHP stays PHP. HTML fragments run component / statement / comment / echo.
 */
final class Pipeline
{
    /**
     * @param $source string
     * @param $state State
     *
     * @return string
     */
    public static function compile(string $source, State $state): string
    {
        $source = preg_replace_callback(
            '/^[ \t]*@markdown[ \t]*\R(.*?)^[ \t]*@endmarkdown[ \t]*$/ms',
            static fn (array $match): string => Markdown::compileView($match[1], $state->echo_format),
            $source,
        ) ?? $source;
        $source = Components::defer_slots($source);

        // Livewire and Filament templates use Blade's `$this` for the current
        // component. Keep Engine internals available through its generated code,
        // and expose the render object through a dedicated template variable.
        $source = preg_replace_callback('/\\$this\\b/', static fn (): string => '$__webkernel_component', $source) ?? $source;

        $out = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token) && $token[0] === T_INLINE_HTML) {
                $html = $token[1];
                $html = Comments::compile($html);
                $html = Components::compile($html);
                $html = Statements::compile($html, $state);
                $html = Echoes::compile($html, $state->echo_format);
                $out .= $html;
                continue;
            }
            $out .= is_array($token) ? $token[1] : $token;
        }
        if ($state->footer !== []) {
            $out = ltrim($out, "\n")."\n".implode("\n", array_reverse($state->footer));
        }

        return $out;
    }
}
