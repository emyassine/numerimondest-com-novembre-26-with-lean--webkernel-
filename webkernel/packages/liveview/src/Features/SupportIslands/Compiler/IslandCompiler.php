<?php

namespace Webkernel\Liveview\Features\SupportIslands\Compiler;

use Webkernel\Liveview\Compiler\Parser\Parser;

class IslandCompiler
{
    protected string $mutableContents;

    protected ?string $imports;

    public function __construct(
        public string $pathSignature,
        public string $contents,
    ) {
        $this->mutableContents = $contents;

        // Islands are extracted into separately compiled views, so any imports
        // from the top of the view need to be carried into each island...
        $this->imports = $this->extractImports();
    }

    public static function compile(string $pathSignature, string $contents): string
    {
        $compiler = new self($pathSignature, $contents);

        return $compiler->process();
    }

    public function process(): string
    {
        $comments = [];
        $contents = preg_replace_callback('/\{\{--(.*?)--\}\}/s', static function (array $match) use (&$comments): string {
            $key = '[WEBKERNEL_ISLAND_COMMENT:'.count($comments).']';
            $comments[$key] = $match[0];
            return $key;
        }, $this->mutableContents) ?? $this->mutableContents;
        $offset = 0;
        $occurrence = 0;
        $result = $this->compileSegment($contents, $offset, false, 1, $occurrence);

        return strtr($result, $comments);
    }

    private function compileSegment(string $source, int &$offset, bool $nested, int $level, int &$occurrence): string
    {
        $result = '';
        $length = strlen($source);
        while (preg_match('/(?<!@)@(?:island\b|endisland\b)/', $source, $match, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $tag = $match[0][0];
            $start = $match[0][1];
            $result .= substr($source, $offset, $start - $offset);
            $offset = $start + strlen($tag);

            if ($tag === '@endisland') {
                if (! $nested) {
                    throw new \InvalidArgumentException('An @endisland directive has no matching @island.');
                }

                return $result;
            }

            [$expression, $offset] = $this->readDirectiveExpression($source, $offset);
            $content = $this->compileSegment($source, $offset, true, $level + 1, $occurrence);

            if (str_contains($content, '@placeholder')) {
                $content = str_replace('@placeholder', '<?php if (isset($__placeholder)) { ob_start(); } if (isset($__placeholder)): ?>', $content);
                $content = str_replace('@endplaceholder', '<?php endif; if (isset($__placeholder)) { echo ob_get_clean(); return; } ?>', $content);
            } else {
                $content = '<?php if (isset($__placeholder)) { echo $__placeholder; return; } ?>' . "\n\n" . $content;
            }

            $result .= $this->compileIsland(++$occurrence, $level, $expression, $content);
        }

        $result .= substr($source, $offset);
        $offset = $length;
        if ($nested) {
            throw new \InvalidArgumentException('An @island directive has no matching @endisland.');
        }

        return $result;
    }

    /** @return array{string, int} */
    private function readDirectiveExpression(string $source, int $offset): array
    {
        $length = strlen($source);
        while ($offset < $length && ctype_space($source[$offset])) { $offset++; }
        if (($source[$offset] ?? '') !== '(') { return ['', $offset]; }

        $start = ++$offset;
        $depth = 1;
        $quote = '';
        $escaped = false;
        for (; $offset < $length; $offset++) {
            $char = $source[$offset];
            if ($quote !== '') {
                if ($escaped) { $escaped = false; }
                elseif ($char === '\\') { $escaped = true; }
                elseif ($char === $quote) { $quote = ''; }
                continue;
            }
            if ($char === "'" || $char === '"' || $char === '`') { $quote = $char; continue; }
            if ($char === '(') { $depth++; continue; }
            if ($char === ')' && --$depth === 0) {
                return [trim(substr($source, $start, $offset - $start)), $offset + 1];
            }
        }

        throw new \InvalidArgumentException('An @island directive has an unclosed expression.');
    }

    public function compileIsland(int $occurrence, int $nestingLevel, string $expression, string $innerContent): string
    {
        // Get the cached path for the extracted island...
        $hash = $this->getPathBasedHash($this->pathSignature);
        $token = $hash . '-' . $occurrence;
        $cachedPath = self::getCachedPathFromToken($token);

        // Build the output directive for the island...
        $output = '@island';

        if (trim($expression) === '') {
            $output .= '(token: \'' . $token . '\')';
        } else {
            $output .= '(' . $expression . ', token: \'' . $token . '\')';
        }

        // Inject scope provider code at the top of the island view...
        $scopeProviderCode = $this->generateScopeProviderCode($expression);
        $innerContent = $scopeProviderCode . $innerContent;

        // Carry imports from the top of the view into the extracted island view...
        if ($this->imports) {
            $innerContent = $this->imports . "\n\n" . $innerContent;
        }

        // Ensure the cached directory exists...
        $directory = dirname($cachedPath);
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Unable to create Livewire island cache directory.');
        }

        // Write the cached island to the file system...
        file_put_contents($cachedPath, $innerContent);

        return $output;
    }

    protected function extractImports(): ?string
    {
        $prefix = explode('@island', $this->contents, 2)[0];

        $imports = [];

        // Collect `use` statements from leading PHP blocks (the compiler hoists
        // an SFC's imports into one of these at the top of the view)...
        while (preg_match('/\A\s*<\?php(.*?)\?>/s', $prefix, $matches)) {
            if ($statements = Parser::extractUseStatements($matches[1])) {
                $imports[] = "<?php\n" . $statements . "\n?>";
            }

            $prefix = substr($prefix, strlen($matches[0]));
        }

        // Preserve `@use(...)` directives so the Webkernel compiler can process them later.
        if (preg_match_all('/(?<![@\w])@use\s*\([^)]*\)/', $prefix, $matches)) {
            $imports = array_merge($imports, $matches[0]);
        }

        return $imports ? implode("\n", $imports) : null;
    }

    protected function generateScopeProviderCode(string $expression): string
    {
        $directiveWithExtraction = '';

        // Only extract directive's "with" if there's an expression
        if (trim($expression) !== '') {
            $directiveWithExtraction = <<<PHP
// Extract directive's "with" parameter (overrides component properties)
\$__islandScope = (function(\$name = null, \$token = null, \$lazy = false, \$defer = false, \$always = false, \$skip = false, \$with = []) {
    return \$with;
})({$expression});
if (!empty(\$__islandScope)) {
    extract(\$__islandScope, EXTR_OVERWRITE);
}


PHP;
        }

        // Always include runtime "with" extraction (even if directive has no parameters)
        return <<<PHP
<?php
{$directiveWithExtraction}// Extract runtime "with" parameter if provided (overrides everything)
if (isset(\$__runtimeWith) && is_array(\$__runtimeWith) && !empty(\$__runtimeWith)) {
    extract(\$__runtimeWith, EXTR_OVERWRITE);
}
?>

PHP;
    }

    public static function getCachedPathFromToken(string $token): string
    {
        $cachedDirectory = base_path('runtime/cache/livewire');

        return $cachedDirectory . '/islands/' . $token . '.view.php';
    }

    public function getPathBasedHash(string $path): string
    {
        return self::pathHash($this->pathSignature);
    }

    public static function pathHash(string $path): string
    {
        $base = rtrim(base_path(), '/\\');
        $relative = str_contains($path, $base) ? substr($path, strpos($path, $base) + strlen($base)) : $path;

        return substr(md5($relative), 0, 8);
    }

    protected function restoreSetAsideDirectives(string $contents, array $directives): string
    {
        return strtr($contents, $directives);
    }

}
