<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View\Compile;

/**
 * `@directive` compile table. Unknown tags are left as-is (`@media` in CSS).
 */
final class Statements
{
    /**
     * @param $html string
     * @param $state State
     *
     * @return string
     */
    public static function compile(string $html, State $state): string
    {
        $replaced = preg_replace_callback(
            '/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x',
            static fn (array $m): string => self::one($m, $state),
            $html,
        );

        return is_string($replaced) ? $replaced : $html;
    }

    /**
     * @param $match array<int, string>
     * @param $state State
     *
     * @return string
     */
    private static function one(array $match, State $state): string
    {
        $name = $match[1];
        if (str_contains($name, '@')) {
            return $name.($match[3] ?? '');
        }
        $expression = $match[3] ?? '';
        $custom = $state->directives->get($name);
        if ($custom !== null) {
            return $custom($expression, $state);
        }
        $builtin = self::builtins()[$name] ?? null;
        if ($builtin !== null) {
            return $builtin($expression, $state);
        }

        return $match[0];
    }

    /**
     * @return array<string, callable(string, State): string>
     */
    private static function builtins(): array
    {
        static $map = null;
        if ($map !== null) {
            return $map;
        }

        $map = [
            'if' => static fn (string $e): string => Php::OPEN.'if'.$e.': ?>',
            'elseif' => static fn (string $e): string => Php::OPEN.'elseif'.$e.': ?>',
            'else' => static fn (string $_): string => Php::OPEN.'else: ?>',
            'endif' => static fn (string $_): string => Php::OPEN.'endif; ?>',
            'unless' => static fn (string $e): string => Php::OPEN.'if ( ! '.$e.'): ?>',
            'endunless' => static fn (string $_): string => Php::OPEN.'endif; ?>',
            'isset' => static fn (string $e): string => Php::OPEN.'if(isset'.$e.'): ?>',
            'endisset' => static fn (string $_): string => Php::OPEN.'endif; ?>',
            'foreach' => static function (string $e, State $state): string {
                [$iterable, $iteration] = self::loop_expression($e, '@foreach');
                $id = ++$state->loop_id;
                $state->loop_stack[] = $id;
                $data = '$__webkernel_loop_data_'.$id;

                return Php::OPEN.$data.' = '.$iterable.'; $__webkernel_engine->addLoop('.$data.'); foreach ('.$data.' as '.$iteration.'): $__webkernel_engine->incrementLoopIndices(); $loop = $__webkernel_engine->getLastLoop(); ?>';
            },
            'endforeach' => static function (string $_, State $state): string {
                if ($state->loop_stack === []) {
                    throw new \InvalidArgumentException('@endforeach has no matching @foreach.');
                }
                array_pop($state->loop_stack);

                return Php::OPEN.'endforeach; $__webkernel_engine->popLoop(); $loop = $__webkernel_engine->getLastLoop(); ?>';
            },
            'for' => static fn (string $e): string => Php::OPEN.'for'.$e.': ?>',
            'endfor' => static fn (string $_): string => Php::OPEN.'endfor; ?>',
            'while' => static fn (string $e): string => Php::OPEN.'while'.$e.': ?>',
            'endwhile' => static fn (string $_): string => Php::OPEN.'endwhile; ?>',
            'break' => static fn (string $e): string => $e !== '' ? Php::OPEN.'if'.$e.' break; ?>' : Php::OPEN.'break; ?>',
            'continue' => static fn (string $e): string => $e !== '' ? Php::OPEN.'if'.$e.' continue; ?>' : Php::OPEN.'continue; ?>',
            'php' => static fn (string $e): string => $e !== '' ? Php::OPEN.Php::strip_parentheses($e).'; ?>' : Php::OPEN,
            'endphp' => static fn (string $_): string => ' ?>',
            'markdown' => static fn (string $e): string => Php::echo('\\Webkernel\\View\\Markdown::render('.Php::strip_parentheses($e).')'),
            'liveviewStyles' => static fn (string $_): string => Php::OPEN.'$__liveview_nonce = htmlspecialchars((string) ($csp_nonce ?? ""), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); echo "<style nonce=\"".$__liveview_nonce."\">[x-cloak], [x-cloak=\"\"], [x-cloak=\"x-cloak\"] { display: none !important; }</style>"; ?>',
            'liveviewScripts' => static fn (string $_): string => Php::OPEN.'$__liveview_script = htmlspecialchars(\\Webkernel\\Getters\\GetAsset::url("webkernel/liveview", "dist/liveview.min.js"), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); echo "<script defer src=\"".$__liveview_script."\"></script>"; ?>',
            'section' => static fn (string $e): string => Php::OPEN.'$this->start_section'.$e.'; ?>',
            'endsection' => static fn (string $_): string => Php::OPEN.'$this->stop_section(); ?>',
            'stop' => static fn (string $_): string => Php::OPEN.'$this->stop_section(); ?>',
            'overwrite' => static fn (string $_): string => Php::OPEN.'$this->stop_section(true); ?>',
            'parent' => static fn (string $_): string => '@parentXYZABC',
            'yield' => static fn (string $e): string => Php::echo('$this->yield_content'.$e),
            'include' => static fn (string $e): string => Php::echo('$this->includeChild(get_defined_vars(), '.Php::strip_parentheses($e).')'),
            'push' => static fn (string $e): string => Php::OPEN.'$this->start_push'.$e.'; ?>',
            'endpush' => static fn (string $_): string => Php::OPEN.'$this->stop_push(); ?>',
            'stack' => static fn (string $e): string => Php::echo(' $this->stack'.$e),
            'once' => static function (string $e): string {
                $key = Php::strip_parentheses($e);
                if ($key === '') {
                    $key = var_export('once_'.bin2hex(random_bytes(8)), true);
                }

                return Php::OPEN.'if ($this->once('.$key.')): ?>';
            },
            'endonce' => static fn (string $_): string => Php::OPEN.'endif; ?>',
            'csrf' => static fn (string $_): string => Php::echo('\\Webkernel\\Csrf::field()->toHtml()'),
            'lang' => static fn (string $e): string => Php::echo('\\lang'.Php::strip_parentheses($e)),
            'this' => static fn (string $_): string => Php::OPEN.'echo "window.Livewire.find(" . json_encode($__webkernel_component->getId()) . ")"; ?>',
            'liveview' => static fn (string $e): string => Php::OPEN.'echo \\Webkernel\Liveview\\Webkernel\\Runtime::mount('.Php::strip_parentheses($e).'); ?>',
            'island' => static fn (string $e): string => Php::OPEN.'if (isset($__webkernel_component)) { echo $__webkernel_component->renderIslandDirective'.$e.'; } ?>',
            'deprecated' => static fn (string $_): string => '',
            'trim' => static fn (string $_): string => Php::OPEN.'ob_start(); ?>',
            'endtrim' => static fn (string $_): string => Php::OPEN.'echo trim((string) ob_get_clean()); ?>',
            'capture' => static function (string $e, State $state): string {
                $arguments = self::split_arguments(Php::strip_parentheses($e));
                $target = array_shift($arguments) ?? '';
                if (preg_match('/\\A\\$[a-zA-Z_][a-zA-Z0-9_]*\\z/', $target) !== 1) {
                    throw new \InvalidArgumentException('@capture requires a variable as its first argument.');
                }
                $id = ++$state->capture_id;
                $scope = '$__view_capture_scope_'.$id;
                $state->capture_stack[] = $scope;
                $parameters = implode(', ', $arguments);

                return Php::OPEN.$scope.' = get_defined_vars(); '.$target.' = function ('.$parameters.') use ('.$scope.') { extract('.$scope.', EXTR_SKIP); ob_start(); ?>';
            },
            'endcapture' => static function (string $_, State $state): string {
                $scope = array_pop($state->capture_stack);
                if ($scope === null) {
                    throw new \InvalidArgumentException('@endcapture has no matching @capture.');
                }

                return Php::OPEN.'return \\Webkernel\\View\\View::html((string) ob_get_clean()); }; unset('.$scope.'); ?>';
            },
            'js' => static fn (string $e): string => Php::OPEN.'echo \\Webkernel\\View\\View::js('.Php::strip_parentheses($e).'); ?>',
            'vite' => static fn (string $e): string => Php::OPEN.'echo \\Webkernel\\View\\View::vite('.Php::strip_parentheses($e).'); ?>',
            'class' => static fn (string $e): string => Php::OPEN.'echo \\Webkernel\\View\\View::class_attribute('.Php::strip_parentheses($e).'); ?>',
            'style' => static fn (string $e): string => Php::OPEN.'echo \\Webkernel\\View\\View::style_attribute('.Php::strip_parentheses($e).'); ?>',
            'disabled' => static fn (string $e): string => Php::OPEN.'echo \\Webkernel\\View\\View::conditional_attribute("disabled", '.Php::strip_parentheses($e).'); ?>',
            'readonly' => static fn (string $e): string => Php::OPEN.'echo \\Webkernel\\View\\View::conditional_attribute("readonly", '.Php::strip_parentheses($e).'); ?>',
            'required' => static fn (string $e): string => Php::OPEN.'echo \\Webkernel\\View\\View::conditional_attribute("required", '.Php::strip_parentheses($e).'); ?>',
            'checked' => static fn (string $e): string => Php::OPEN.'echo \\Webkernel\\View\\View::conditional_attribute("checked", '.Php::strip_parentheses($e).'); ?>',
            'selected' => static fn (string $e): string => Php::OPEN.'echo \\Webkernel\\View\\View::conditional_attribute("selected", '.Php::strip_parentheses($e).'); ?>',
            'auth' => static fn (string $_): string => Php::OPEN.'if (\\Webkernel\\Accounts\\Auth::user() !== null): ?>',
            'endauth' => static fn (string $_): string => Php::OPEN.'endif; ?>',
            'guest' => static fn (string $_): string => Php::OPEN.'if (\\Webkernel\\Accounts\\Auth::user() === null): ?>',
            'endguest' => static fn (string $_): string => Php::OPEN.'endif; ?>',
            'slot' => static fn (string $e): string => Php::OPEN.' $this->slot'.$e.'; ?>',
            'endslot' => static fn (string $_): string => Php::OPEN.' $this->end_slot(); ?>',
            'props' => static function (string $e): string {
                $list = Php::strip_parentheses($e);

                return Php::OPEN
                    .' $__props = '.$list.';'
                    .' foreach ($__props as $__n => $__d) { if (\\is_int($__n)) { unset($__props[$__n]); $__n = $__d; $__d = null; $__props[$__n] = null; } if (!isset($$__n)) { $$__n = $__d; } }'
                    .' $attributes = (isset($attributes) && $attributes instanceof \\Webkernel\\View\\AttributeBag'
                    .' ? $attributes : new \\Webkernel\\View\\AttributeBag([]))->except(\\array_keys($__props));'
                    .' unset($__props, $__n, $__d); ?>';
            },
            'component' => static fn (string $e): string => Php::OPEN.' $this->start_component'.$e.'; ?>',
            'endcomponent' => static fn (string $_): string => Php::echo('$this->render_component()'),
        ];

        $map['forelse'] = static function (string $e, State $state): string {
            [$iterable, $iteration] = self::loop_expression($e, '@forelse');
            $empty = '$__empty_'.++$state->forelse;
            $id = ++$state->loop_id;
            $state->loop_stack[] = $id;
            $data = '$__webkernel_loop_data_'.$id;

            return Php::OPEN.$data.' = '.$iterable.'; $__webkernel_engine->addLoop('.$data.'); '.$empty.' = true; foreach ('.$data.' as '.$iteration.'): $__webkernel_engine->incrementLoopIndices(); $loop = $__webkernel_engine->getLastLoop(); '.$empty.' = false; ?>';
        };
        $map['empty'] = static function (string $e, State $state): string {
            if ($e === '') {
                if ($state->loop_stack === []) {
                    throw new \InvalidArgumentException('@empty has no matching @forelse.');
                }
                array_pop($state->loop_stack);
                $empty = '$__empty_'.$state->forelse--;

                return Php::OPEN.'endforeach; $__webkernel_engine->popLoop(); $loop = $__webkernel_engine->getLastLoop(); if ('.$empty.'): ?>';
            }

            return Php::OPEN.'if (empty'.$e.'): ?>';
        };
        $map['endforelse'] = static fn (string $_): string => Php::OPEN.'endif; ?>';
        $map['endempty'] = static fn (string $_): string => Php::OPEN.'endif; ?>';

        $map['extends'] = static function (string $e, State $state): string {
            $id = ++$state->extend_id;
            $view = Php::strip_parentheses($e);
            $state->footer[] = Php::OPEN.'if (isset($_shouldextend['.$id.'])) { echo $this->includeChild(get_defined_vars(), '.$view.'); } ?>';

            return Php::OPEN.'$_shouldextend['.$id.']=1; ?>';
        };

        $map['can'] = static fn (string $e, State $state): string => Php::OPEN.'if ('.self::acl($e, $state, 'can').'): ?>';
        $map['cannot'] = static fn (string $e, State $state): string => Php::OPEN.'if (! '.self::acl($e, $state, 'can').'): ?>';
        $map['canany'] = static fn (string $e, State $state): string => Php::OPEN.'if ('.self::acl($e, $state, 'canAny').'): ?>';
        $map['elsecan'] = static fn (string $e, State $state): string => Php::OPEN.'elseif ('.self::acl($e, $state, 'can').'): ?>';
        $map['elsecannot'] = static fn (string $e, State $state): string => Php::OPEN.'elseif (! '.self::acl($e, $state, 'can').'): ?>';
        $map['elsecanany'] = static fn (string $e, State $state): string => Php::OPEN.'elseif ('.self::acl($e, $state, 'canAny').'): ?>';
        $map['endcan'] = static fn (string $_): string => Php::OPEN.'endif; ?>';
        $map['endcannot'] = static fn (string $_): string => Php::OPEN.'endif; ?>';
        $map['endcanany'] = static fn (string $_): string => Php::OPEN.'endif; ?>';

        return $map;
    }

    /** @return array{string, string} */
    private static function loop_expression(string $expression, string $directive): array
    {
        if (preg_match('/\\A\\(\\s*(.+)\\s+as\\s+(.+)\\)\\z/s', $expression, $matches) !== 1) {
            throw new \InvalidArgumentException('Malformed '.$directive.' statement.');
        }

        return [trim($matches[1]), trim($matches[2])];
    }

    /** @return list<string> */
    private static function split_arguments(string $arguments): array
    {
        $parts = [];
        $start = 0;
        $depth = 0;
        $quote = '';
        $escaped = false;
        $length = strlen($arguments);
        for ($index = 0; $index < $length; $index++) {
            $char = $arguments[$index];
            if ($quote !== '') {
                if ($escaped) { $escaped = false; }
                elseif ($char === '\\\\') { $escaped = true; }
                elseif ($char === $quote) { $quote = ''; }
                continue;
            }
            if ($char === "'" || $char === '"' || $char === '`') { $quote = $char; continue; }
            if ($char === '(' || $char === '[' || $char === '{') { $depth++; continue; }
            if ($char === ')' || $char === ']' || $char === '}') { $depth--; continue; }
            if ($char === ',' && $depth === 0) {
                $parts[] = trim(substr($arguments, $start, $index - $start));
                $start = $index + 1;
            }
        }
        if ($start < $length || $arguments !== '') { $parts[] = trim(substr($arguments, $start)); }

        return $parts;
    }

    /**
     * @param $expression string
     * @param $state State
     * @param $method string
     *
     * @return string
     */
    private static function acl(string $expression, State $state, string $method): string
    {
        $v = Php::strip_parentheses($expression);
        return '\\Webkernel\\Accounts\\Auth::'.$method.'('.$v.')';
    }
}
