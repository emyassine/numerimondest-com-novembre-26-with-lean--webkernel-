<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel;

use InvalidArgumentException;
use RuntimeException;

final class GenerateCode
{
    /**
     * Render a trusted source stub and write the generated file.
     *
     * @param array<string, scalar> $values
     */
    public static function make(
        string $source,
        string $destination,
        array $values = [],
        bool $is_strict = true,
        string $header = '',
    ): string {
        $contents = file_get_contents($source);
        if ($contents === false) {
            throw new RuntimeException("Unable to read code stub [{$source}].");
        }

        $replacements = [];
        foreach ($values as $name => $value) {
            $replacements['{{ ' . $name . ' }}'] = (string) $value;
        }

        $contents = strtr($contents, $replacements);
        if (preg_match('/\{\{\s*[a-zA-Z_][a-zA-Z0-9_.]*\s*\}\}/', $contents) === 1) {
            throw new InvalidArgumentException("Code stub [{$source}] contains an unresolved placeholder.");
        }

        if ($is_strict) {
            if (! str_starts_with($contents, '<?php')) {
                throw new InvalidArgumentException('Strict mode requires a PHP source stub.');
            }

            if (! str_contains(substr($contents, 0, 100), 'declare(strict_types=1)')) {
                $contents = preg_replace('/^<\?php\s*/', "<?php declare(strict_types=1);\n", $contents, 1) ?? $contents;
            }
        }

        if ($header !== '') {
            if (! str_starts_with($contents, '<?php')) {
                throw new InvalidArgumentException('A PHP header can only be added to a PHP source stub.');
            }

            $php_opening_length = strpos($contents, "\n");
            if ($php_opening_length === false) {
                throw new RuntimeException("Code stub [{$source}] has no PHP opening line.");
            }

            $contents = substr_replace($contents, "\n" . rtrim($header) . "\n", $php_opening_length, 0);
        }

        $directory = dirname($destination);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create code destination directory [{$directory}].");
        }

        $written = file_put_contents($destination, $contents, LOCK_EX);
        if ($written !== strlen($contents)) {
            throw new RuntimeException("Unable to write generated code to [{$destination}].");
        }

        return $destination;
    }
}
