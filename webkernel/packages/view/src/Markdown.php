<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\View;

final class Markdown
{
    /** Compile an inline Markdown block; view echoes stay escaped exactly once. */
    public static function compileView(string $source, string $echo_format = '\\Webkernel\\View\\View::echo(%s)'): string
    {
        preg_match_all('/^[ \t]*(?=\S)/m', $source, $indents);
        $indent = $indents[0] === [] ? 0 : min(array_map('strlen', $indents[0]));
        if ($indent > 0) $source = preg_replace('/^[ \t]{' . $indent . '}/m', '', $source) ?? $source;
        $echoes = [];
        $prefix = 'WKMD' . hash('sha256', $source);
        // Code examples remain literal, including any view expressions they contain.
        $source = preg_replace_callback('/(```[^\n]*\n.*?\n[ \t]*```|`[^`\n]+`)|(@?\{\{.*?\}\}|@?\{!!.*?!!\})/s', static function (array $match) use (&$echoes, $prefix, $echo_format): string {
            if ($match[1] !== '') return $match[0];
            $key = $prefix . count($echoes) . 'END';
            $echoes[$key] = Compile\Echoes::compile($match[2], $echo_format);
            return $key;
        }, $source) ?? $source;
        $view_fragments = [];
        $source = self::protectViewFragments($source, $view_fragments, $prefix);

        return strtr(strtr(self::render($source), $view_fragments), $echoes);
    }

    public static function renderEmbedded(string $source): string
    {
        return preg_replace('/```view\s+parse\+\s*\R(.*?)\R```/s', '$1', $source) ?? $source;
    }

    public static function viewSource(string $source): string
    {
        preg_match_all('/```view\s+parse\+\s*\R(.*?)\R```/s', $source, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        $parts = [];
        $cursor = 0;
        foreach ($matches as $match) {
            $offset = $match[0][1];
            $parts[] = self::render(substr($source, $cursor, $offset - $cursor));
            $parts[] = $match[1][0];
            $cursor = $offset + strlen($match[0][0]);
        }
        $parts[] = self::render(substr($source, $cursor));
        return implode("\n", $parts);
    }

    public static function render(string $source, bool $allow_html = false): string
    {
        // Allow trusted, literal price expressions in Markdown-backed content,
        // including content passed to the @markdown($content) directive.
        $source = preg_replace_callback(
            '/(```[\s\S]*?```|`[^`\n]+`)|(\{\{\s*price\(\s*(-?\d+(?:\.\d+)?)\s*,\s*[\'\"]([A-Z]{3})[\'\"]\s*\)\s*\}\})/',
            static function (array $match): string {
                if (($match[1] ?? '') !== '') {
                    return $match[1];
                }

                return function_exists('price')
                    ? price((float) $match[3], $match[4])
                    : $match[2];
            },
            $source,
        ) ?? $source;

        static $rendered = [];
        $source_key = hash('sha256', ($allow_html ? 'html' : 'escaped')."\0".$source);
        if (isset($rendered[$source_key])) {
            return $rendered[$source_key];
        }
        $cache_key = 'webkernel:markdown:v4:'.$source_key;
        $apcu_enabled = function_exists('apcu_fetch')
            && function_exists('apcu_store')
            && (! function_exists('apcu_enabled') || apcu_enabled());
        if ($apcu_enabled) {
            $cached = apcu_fetch($cache_key, $cache_hit);
            if ($cache_hit && is_string($cached)) {
                return $rendered[$source_key] = $cached;
            }
        }

        $lines = preg_split('/\R/', str_replace(["\r\n", "\r"], "\n", trim($source))) ?: [];
        $html = [];
        $paragraph = [];
        $flushParagraph = static function () use (&$paragraph, &$html, $allow_html): void {
            if ($paragraph !== []) {
                $html[] = '<p>' . self::inline(implode(' ', $paragraph), $allow_html) . '</p>';
            }
            $paragraph = [];
        };
        for ($i = 0, $count = count($lines); $i < $count; $i++) {
            $line = $lines[$i];
            if (preg_match('/^WKMD[0-9a-f]{64}VIEW[0-9]+END$/', trim($line)) === 1) {
                $flushParagraph();
                $html[] = trim($line);
                continue;
            }
            if (trim($line) === '') { $flushParagraph(); continue; }
            if ($allow_html && preg_match('/^\s*<(?:address|article|aside|blockquote|body|caption|center|dd|details|dialog|div|dl|dt|fieldset|figcaption|figure|footer|form|h[1-6]|header|hr|html|iframe|main|menu|nav|ol|p|pre|script|section|style|table|ul|video)\b/i', $line) === 1) {
                $flushParagraph();
                $block = [$line];
                while ($i + 1 < $count && trim($lines[$i + 1]) !== '') {
                    $block[] = $lines[++$i];
                }
                $html[] = implode("\n", $block);
                continue;
            }
            if (preg_match('/^```([a-zA-Z0-9_-]*)\s*$/', $line, $fence)) {
                $flushParagraph();
                $code = [];
                while (++$i < $count && !preg_match('/^```\s*$/', $lines[$i])) $code[] = $lines[$i];
                $language = $fence[1] !== '' ? ' class="language-' . htmlspecialchars($fence[1], ENT_QUOTES, 'UTF-8') . '"' : '';
                $escaped_code = htmlspecialchars(implode("\n", $code), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $html[] = $fence[1] === 'mermaid'
                    ? '<pre class="mermaid">' . $escaped_code . '</pre>'
                    : '<pre><code' . $language . '>' . $escaped_code . '</code></pre>';
                continue;
            }
            if (
                $i + 1 < $count
                && str_contains($line, '|')
                && preg_match('/^\s*\|?\s*:?-{3,}:?\s*(?:\|\s*:?-{3,}:?\s*)+\|?\s*$/', $lines[$i + 1]) === 1
            ) {
                $flushParagraph();
                $headers = self::tableCells($line);
                $alignments = array_map(self::tableAlignment(...), self::tableCells($lines[++$i]));
                $table_rows = [];
                while ($i + 1 < $count && trim($lines[$i + 1]) !== '' && str_contains($lines[$i + 1], '|')) {
                    $table_rows[] = self::tableCells($lines[++$i]);
                }
                $html[] = self::table($headers, $alignments, $table_rows, $allow_html);
                continue;
            }
            if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $heading)) {
                $flushParagraph();
                $level = strlen($heading[1]);
                $html[] = '<h' . $level . ' class="markdown-heading">' . self::inline($heading[2], $allow_html) . '</h' . $level . '>';
                continue;
            }
            if (preg_match('/^\s*(?:---+|___+|\*\*\*+)\s*$/', $line)) { $flushParagraph(); $html[] = '<hr>'; continue; }
            if (preg_match('/^>\s?(.*)$/', $line, $quote)) { $flushParagraph(); $html[] = '<blockquote><p>' . self::inline($quote[1], $allow_html) . '</p></blockquote>'; continue; }
            if (preg_match('/^\s*[-*+]\s+(.+)$/', $line)) {
                $flushParagraph();
                $items = [];
                while ($i < $count && preg_match('/^\s*[-*+]\s+(.+)$/', $lines[$i], $item)) { $items[] = '<li>' . self::inline($item[1], $allow_html) . '</li>'; $i++; }
                $i--;
                $html[] = '<ul>' . implode('', $items) . '</ul>';
                continue;
            }
            if (preg_match('/^\s*\d+\.\s+(.+)$/', $line)) {
                $flushParagraph();
                $items = [];
                while ($i < $count && preg_match('/^\s*\d+\.\s+(.+)$/', $lines[$i], $item)) { $items[] = '<li>' . self::inline($item[1], $allow_html) . '</li>'; $i++; }
                $i--;
                $html[] = '<ol>' . implode('', $items) . '</ol>';
                continue;
            }
            $paragraph[] = trim($line);
        }
        $flushParagraph();
        $rendered_html = implode("\n", $html);
        $rendered[$source_key] = $rendered_html;
        if ($apcu_enabled) {
            apcu_store($cache_key, $rendered_html, 86400);
        }

        return $rendered_html;
    }

    /** @param $source string @param $fragments array<string, string> @param $prefix string */
    private static function protectViewFragments(string $source, array &$fragments, string $prefix): string
    {
        $lines = preg_split('/\R/', $source) ?: [];
        $output = [];
        $count = count($lines);
        for ($i = 0; $i < $count; $i++) {
            $line = $lines[$i];
            if (preg_match('/^[ \t]*```view\s+parse\+[ \t]*$/', $line) === 1) {
                $fragment = [];
                while (++$i < $count && preg_match('/^[ \t]*```[ \t]*$/', $lines[$i]) !== 1) {
                    $fragment[] = $lines[$i];
                }
                $output[] = self::storeViewFragment(implode("\n", $fragment), $fragments, $prefix);
                continue;
            }

            if (preg_match('/^[ \t]*```/', $line) === 1) {
                $output[] = $line;
                while (++$i < $count) {
                    $output[] = $lines[$i];
                    if (preg_match('/^[ \t]*```[ \t]*$/', $lines[$i]) === 1) {
                        break;
                    }
                }
                continue;
            }

            if (
                preg_match('/^[ \t]*@[A-Za-z_][A-Za-z0-9_]*(?:::\w+)?(?:[ \t]*\(.*\))?[ \t]*$/', $line) === 1
                || preg_match('/<\/?[A-Za-z][A-Za-z0-9:-]*(?:-[A-Za-z0-9:-]+)*(?:\s|\/?>)/', $line) === 1
            ) {
                $output[] = self::storeViewFragment(trim($line), $fragments, $prefix);
                continue;
            }

            $output[] = $line;
        }

        return implode("\n", $output);
    }

    /** @param $fragment string @param $fragments array<string, string> @param $prefix string @return string */
    private static function storeViewFragment(string $fragment, array &$fragments, string $prefix): string
    {
        $key = $prefix . 'VIEW' . count($fragments) . 'END';
        $fragments[$key] = $fragment;

        return $key;
    }

    /** @return list<string> */
    private static function tableCells(string $row): array
    {
        $row = trim($row);
        if (str_starts_with($row, '|')) {
            $row = substr($row, 1);
        }
        if (str_ends_with($row, '|')) {
            $row = substr($row, 0, -1);
        }

        return array_map('trim', explode('|', $row));
    }

    private static function tableAlignment(string $separator): string
    {
        $left = str_starts_with($separator, ':');
        $right = str_ends_with($separator, ':');

        return $left && $right ? 'center' : ($right ? 'right' : ($left ? 'left' : ''));
    }

    /** @param list<string> $headers @param list<string> $alignments @param list<list<string>> $rows */
    private static function table(array $headers, array $alignments, array $rows, bool $allow_html): string
    {
        $render_row = static function (array $cells, string $tag) use ($alignments, $allow_html): string {
            $html = '<tr>';
            foreach ($cells as $index => $cell) {
                $alignment = $alignments[$index] ?? '';
                $style = $alignment !== '' ? ' style="text-align:'.$alignment.'"' : '';
                $scope = $tag === 'th' ? ' scope="col"' : '';
                $html .= '<'.$tag.$scope.$style.'>'.self::inline($cell, $allow_html).'</'.$tag.'>';
            }

            return $html.'</tr>';
        };
        $html = '<div class="markdown-table-wrap"><table><thead>'.$render_row($headers, 'th').'</thead><tbody>';
        foreach ($rows as $row) {
            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            }
            $html .= $render_row(array_slice($row, 0, count($headers)), 'td');
        }

        return $html.'</tbody></table></div>';
    }

    private static function inline(string $source, bool $allow_html = false): string
    {
        $tokens = [];
        $source = preg_replace_callback('/`([^`]+)`/', static function (array $match) use (&$tokens): string {
            $key = "\x1A" . count($tokens) . "\x1A";
            $tokens[$key] = '<code>' . htmlspecialchars($match[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code>';
            return $key;
        }, $source) ?? $source;
        if ($allow_html) {
            $source = preg_replace_callback('/<!--.*?-->|<\/?[A-Za-z][A-Za-z0-9:-]*(?:\s+(?:"[^"]*"|\'[^\']*\'|[^\'">])*)?\s*\/?>/s', static function (array $match) use (&$tokens): string {
                $key = "\x1A" . count($tokens) . "\x1A";
                $tokens[$key] = $match[0];
                return $key;
            }, $source) ?? $source;
        }
        $escaped = htmlspecialchars($source, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escaped = preg_replace_callback('/!\[([^\]]*)\]\(([^)\s]+)(?:\s+(&quot;|&#039;)(.*?)\3)?\)/', static function (array $match): string {
            $url = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (!self::safeUrl($url)) return $match[0];
            $title = ($match[3] ?? '') !== '' ? ' title="'.htmlspecialchars(html_entity_decode($match[4], ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"' : '';
            return '<img src="' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" alt="' . $match[1] . '"'.$title.'>';
        }, $escaped) ?? $escaped;
        $escaped = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', static function (array $match): string {
            $url = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (!self::safeUrl($url)) return $match[0];
            return '<a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . $match[1] . '</a>';
        }, $escaped) ?? $escaped;
        $escaped = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/s', '<em>$1</em>', $escaped) ?? $escaped;
        $escaped = preg_replace('/~~(.+?)~~/s', '<del>$1</del>', $escaped) ?? $escaped;
        return strtr($escaped, $tokens);
    }

    private static function safeUrl(string $url): bool
    {
        return !preg_match('/[\x00-\x20\x7f\\\\]/', $url)
            && !str_starts_with($url, '//')
            && (preg_match('/\A(?:\/(?!\/)|#|https?:\/\/|mailto:|tel:)/i', $url) === 1
                || (preg_match('/\A[a-z][a-z0-9+.-]*:/i', $url) !== 1 && !str_starts_with($url, '?')));
    }
}
