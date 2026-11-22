<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View\Compile;

use InvalidArgumentException;
use Webkernel\View\Compile\Php;

/**
 * `<x-ns::name>` and `<x-name>` become start_component / render_component.
 *
 * //> Literal `<x-webkernel::icon name="...">` folds to SVG at compile time.
 */
final class Components
{
    /**
     * Wrap deferred component slots before PHP tokens split their opening and closing tags.
     *
     * @param $source string
     *
     * @return string
     */
    public static function defer_slots(string $source): string
    {
        $source = self::defer_component_tags($source, true);

        return self::defer_component_tags($source, false);
    }

    /**
     * @param $source string
     * @param $namespaced bool
     *
     * @return string
     */
    private static function defer_component_tags(string $source, bool $namespaced): string
    {
        $pattern = $namespaced
            ? '/<(?:x-)?([a-z0-9.-]+)::([a-z0-9.-]+)((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/i'
            : '/<x-([a-z0-9.-]+)((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/i';
        $result = '';
        $offset = 0;
        $length = strlen($source);

        while (preg_match($pattern, $source, $match, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $opening = $match[0][0];
            $start = $match[0][1];
            $opening_end = $start + strlen($opening);
            $attributes = $namespaced ? ($match[3][0] ?? '') : ($match[2][0] ?? '');
            $result .= substr($source, $offset, $start - $offset);
            $offset = $opening_end;

            if (str_ends_with(rtrim($opening, '>'), '/')) {
                $result .= $opening;

                continue;
            }

            $component = $namespaced ? $match[2][0] : $match[1][0];
            $namespace = $namespaced ? $match[1][0] : '';
            $closing_tags = $namespaced
                ? ['</x-'.$namespace.'::'.$component.'>', '</'.$namespace.'::'.$component.'>']
                : ['</x-'.$component.'>'];
            $closing_position = false;
            $closing_tag = '';
            foreach ($closing_tags as $candidate) {
                $candidate_position = stripos($source, $candidate, $offset);
                if ($candidate_position !== false && ($closing_position === false || $candidate_position < $closing_position)) {
                    $closing_position = $candidate_position;
                    $closing_tag = $candidate;
                }
            }
            if ($closing_position === false) {
                $result .= $opening;

                continue;
            }

            $inner = substr($source, $offset, $closing_position - $offset);
            $inner = self::defer_slots($inner);
            $result .= self::deferred_component($opening, $inner, $closing_tag, $attributes, $namespace, $component);
            $offset = $closing_position + strlen($closing_tag);
        }

        if ($offset < $length) {
            $result .= substr($source, $offset);
        }

        return $result;
    }

    /**
     * @param $opening string
     * @param $inner string
     * @param $closing string
     * @param $attributes string
     * @param $namespace string
     * @param $component string
     *
     * @return string
     */
    private static function deferred_component(
	    	string $opening,
	    	string $inner,
	    	string $closing,
	    	string $attributes,
	    	string $namespace,
	    	string $component
    	): string
    {
        if ($inner === '' || preg_match('/(?:^|\s)defer-slot(?:\s|=|$)/i', $attributes) !== 1) {
            return $opening.$inner.$closing;
        }

        $params = self::params($attributes);
        $component_name = $namespace !== '' ? $namespace.'::'.$component : 'components.'.$component;
        $call = '('.var_export($component_name, true).','.$params.')';
        $method = $namespace !== '' ? 'start_component' : 'start_global_component';
        $deferred = Php::OPEN.' $this->'.$method.$call.'; $__slot_scope = get_defined_vars(); $this->slot(\'slot\', function () use ($__slot_scope): string { extract($__slot_scope, EXTR_SKIP); ob_start(); ?>'.$inner.'<?php return trim((string) ob_get_clean()); }); unset($__slot_scope); ?>'.Php::echo('$this->render_component()');

        return self::guard_multipage_slot($deferred, $namespace, $component, $attributes);
    }

    /**
     * @param $html string
     *
     * @return string
     * @throws InvalidArgumentException
     */
    public static function compile(string $html): string
    {
        $html = self::replace(
            $html,
            '/<x-renderhook\s+id=(["\'])([a-zA-Z0-9_.-]+)\1(?:\s+variant=(["\'])([a-zA-Z0-9_.-]+)\3)?\s*(\/?)>/i',
            static function (array $match): string {
                $id = var_export($match[2], true);
                if ($match[5] === '/') {
                    if (($match[4] ?? '') !== '') {
                        throw new InvalidArgumentException('Render hook variants are selected by the receiving component.');
                    }
                    return Php::echo('($renderhooks ?? null)?->render('.$id.') ?? \'\'');
                }

                $variant = var_export($match[4] !== '' ? $match[4] : 'default', true);

                return Php::OPEN.'$this->defineRenderHook('.$id.', variant: '.$variant.', content: function (array $__renderhook_scope): void {'
                    .' extract($__renderhook_scope, EXTR_SKIP); ?>';
            },
        );
        $html = (string) preg_replace(
            '/<\/x-renderhook\s*>/i',
            Php::OPEN.'}, scope: get_defined_vars()); ?>',
            $html,
        );
        if (preg_match('/<\/?x-renderhook\b/i', $html) === 1) {
            throw new InvalidArgumentException('Render hooks require a literal id and an optional literal variant.');
        }

        $html = \preg_replace_callback(
            '/<x-slot\s+name=(["\'])([^"\']+)\1\s*>/i',
            static function (array $m): string {
                return Php::OPEN.' $this->slot('.var_export($m[2], true).'); ?>';
            },
            $html,
        ) ?? $html;
        $html = (string) \preg_replace('/<\/x-slot>/i', Php::OPEN.' $this->end_slot(); ?>', $html);

        $html = self::replace(
            $html,
            '/<(?:x-)?([a-z0-9.-]+)::([a-z0-9.-]+)(\s(?:[^>"\']|"[^"]*"|\'[^\']*\')*)?(>((?:(?!<\/(?:x-)?\1::\2>).)*)<\/(?:x-)?\1::\2>|\/>)/ms',
            static function (array $m): string {
                $inner = $m[5] ?? '';
                if ($inner !== '') {
                    $inner = self::compile($inner);
                }
                $folded = self::fold_icon($m[1], $m[2], $m[3] ?? '');
                if ($folded !== null) {
                    return $folded.$inner;
                }
                $call = "('".$m[1].'::'.$m[2]."',".self::params($m[3] ?? '').')';

                if ($inner !== '' && preg_match('/(?:^|\s)defer-slot(?:\s|=|$)/i', $m[3] ?? '') === 1) {
                    $deferred = Php::OPEN.' $this->start_component'.$call.'; $__slot_scope = get_defined_vars(); $this->slot(\'slot\', function () use ($__slot_scope): string { extract($__slot_scope, EXTR_SKIP); ob_start(); ?>'.$inner.'<?php return trim((string) ob_get_clean()); }); unset($__slot_scope); ?>'.Php::echo('$this->render_component()');

                    return self::guard_multipage_slot($deferred, $m[1], $m[2], $m[3] ?? '');
                }

                return Php::OPEN.' $this->start_component'.$call.'; ?>'.$inner.Php::echo('$this->render_component()');
            },
        );
        $html = self::replace(
            $html,
            '/<(?:x-)?([a-z0-9.-]+)::([a-z0-9.-]+)(\s(?:[^>"\']|"[^"]*"|\'[^\']*\')*?)?(?<!\/)>/ms',
            static fn (array $m): string => Php::OPEN.' $this->start_component(\''.$m[1].'::'.$m[2].'\','.self::params($m[3] ?? '').'); ?>',
        );
        $html = self::replace(
            $html,
            '/<\/(?:x-)?([a-z0-9.-]+)::([a-z0-9.-]+)\s*>/i',
            static fn (): string => Php::echo('$this->render_component()'),
        );

        $html = self::replace(
            $html,
            '/<x-([a-z0-9.-]+)(\s(?:[^>"\']|"[^"]*"|\'[^\']*\')*)?(>((?:(?!<\/x-\1>).)*)<\/x-\1>|\/>)/ms',
            static function (array $m): string {
                $inner = $m[4] ?? '';
                if ($inner !== '' && str_contains($m[0], 'x-')) {
                    $inner = self::compile($inner);
                }
                $call = "('components.".$m[1]."',".self::params($m[2] ?? '').')';

                if ($inner !== '' && preg_match('/(?:^|\s)defer-slot(?:\s|=|$)/i', $m[2] ?? '') === 1) {
                    $deferred = Php::OPEN.' $this->start_global_component'.$call.'; $__slot_scope = get_defined_vars(); $this->slot(\'slot\', function () use ($__slot_scope): string { extract($__slot_scope, EXTR_SKIP); ob_start(); ?>'.$inner.'<?php return trim((string) ob_get_clean()); }); unset($__slot_scope); ?>'.Php::echo('$this->render_component()');

                    return self::guard_multipage_slot($deferred, '', $m[1], $m[2] ?? '');
                }

                return Php::OPEN.' $this->start_global_component'.$call.'; ?>'.$inner.Php::echo('$this->render_component()');
            },
        );
        $html = self::replace(
            $html,
            '/<x-([a-z0-9.-]+)(\s(?:[^>"\']|"[^"]*"|\'[^\']*\')*?)?(?<!\/)>/ms',
            static fn (array $m): string => Php::OPEN.' $this->start_global_component(\'components.'.$m[1].'\','.self::params($m[2] ?? '').'); ?>',
        );

        return self::replace(
            $html,
            '/<\/x-([a-z0-9.-]+)\s*>/i',
            static fn (): string => Php::echo('$this->render_component()'),
        );
    }

    /**
     * @param $html string
     * @param $pattern string
     * @param $callback callable(array<int, string>): string
     *
     * @return string
     */
    private static function replace(string $html, string $pattern, callable $callback): string
    {
        $replaced = preg_replace_callback($pattern, $callback, $html);

        return is_string($replaced) ? $replaced : $html;
    }

    /**
     * @param $namespace string
     * @param $name string
     * @param $params string
     *
     * @return string|null
     */
    private static function fold_icon(string $namespace, string $name, string $params): ?string
    {
        if ($namespace !== 'webkernel' || $name !== 'icon') {
            return null;
        }
        if (preg_match('/(?:^|\s):[a-zA-Z]/', $params) === 1) {
            return null;
        }
        $icon = '';
        $set = 'lucide';
        if (preg_match('/\bname="([^"]+)"/', $params, $m) === 1) {
            $icon = $m[1];
        }
        if (preg_match('/\bset="([^"]+)"/', $params, $m) === 1) {
            $set = $m[1];
        }
        if ($icon === '' || ! function_exists('icon')) {
            return null;
        }
        $markup = icon($icon, 'w-icon-svg', '', $set);
        if ($markup === '') {
            return null;
        }

        return '<span class="w-icon">'.$markup.'</span>';
    }

    /**
     * @param $compiled string
     * @param $namespace string
     * @param $component string
     * @param $params string
     *
     * @return string
     */
    private static function guard_multipage_slot(string $compiled, string $namespace, string $component, string $params): string
    {
        if ($namespace !== 'webkernel' || $component !== 'page.multipage.subpage') {
            return $compiled;
        }
        if (preg_match('/(?:^|\s)sub-url\s*=\s*(["\'])([^"\']+)\1/i', $params, $match) !== 1) {
            return $compiled;
        }

        $condition = '($multipage_current ?? \'\') === '.var_export($match[2], true);
        if (preg_match('/(?:^|\s)default(?:\s|$)/i', $params) === 1) {
            $condition = '('.$condition.' || ($multipage_current ?? \'\') === \'\')';
        }

        return Php::OPEN.'if ('.$condition.'): ?>'.$compiled.Php::OPEN.'endif; ?>';
    }

    /**
     * @param $params string
     *
     * @return string
     */
    private static function params(string $params): string
    {
        $params = trim($params);
        if ($params === '' || $params === '/') {
            return '[]';
        }
        $out = [];
        $attribute_spreads = [];
        $offset = 0;
        $len = strlen($params);
        while ($offset < $len) {
            if (preg_match('/\s+/A', $params, $ws, 0, $offset) === 1) {
                $offset += strlen($ws[0]);

                continue;
            }
            if ($params[$offset] === '/') {
                break;
            }
            if (substr($params, $offset, 2) === '{{') {
                $end = self::blade_echo_end($params, $offset + 2);
                if ($end === null) {
                    break;
                }
                $attribute_spreads[] = trim(substr($params, $offset + 2, $end - $offset - 2));
                $offset = $end + 2;

                continue;
            }
            if (preg_match('/:?[A-Za-z0-9_:-]+/A', $params, $m, 0, $offset) !== 1) {
                break;
            }
            $raw = $m[0];
            $offset += strlen($raw);
            $bound = str_starts_with($raw, ':');
            $key = str_replace('-', '_', $bound ? substr($raw, 1) : $raw);
            if (preg_match('/\s*=\s*/A', $params, $eq, 0, $offset) === 1) {
                $offset += strlen($eq[0]);
                $quote = $params[$offset] ?? '';
                if ($quote === '"' || $quote === "'") {
                    $end = strpos($params, $quote, $offset + 1);
                    $value = $end === false ? '' : substr($params, $offset + 1, $end - $offset - 1);
                    $offset = $end === false ? $len : $end + 1;
                } else {
                    preg_match('/[^\s]+/A', $params, $vm, 0, $offset);
                    $value = $vm[0] ?? '';
                    $offset += strlen($value);
                }
                $out[] = $bound
                    ? var_export($key, true).'=>'.$value
                    : var_export($key, true).'=>'.var_export($value, true);

                continue;
            }
            $out[] = var_export($key, true).'=>true';
        }

        if ($attribute_spreads !== []) {
            $out[] = "'__attribute_spreads'=>[".implode(',', $attribute_spreads).']';
        }

        return '['.implode(',', $out).']';
    }

    /** Find the closing Blade echo while respecting quoted and nested PHP expressions. */
    private static function blade_echo_end(string $source, int $offset): ?int
    {
        $quote = '';
        $escaped = false;
        $depth = 0;
        $length = strlen($source);
        for ($index = $offset; $index < $length; $index++) {
            $character = $source[$index];
            if ($quote !== '') {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    $quote = '';
                }

                continue;
            }
            if ($character === "'" || $character === '"' || $character === '`') {
                $quote = $character;

                continue;
            }
            if ($character === '(' || $character === '[' || $character === '{') {
                $depth++;
            } elseif (($character === ')' || $character === ']' || $character === '}') && $depth > 0) {
                $depth--;
            } elseif ($depth === 0 && substr($source, $index, 2) === '}}') {
                return $index;
            }
        }

        return null;
    }
}
