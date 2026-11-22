<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Compiler;

use InvalidArgumentException;
use RuntimeException;
use Webkernel\Platform\Runtime\Ability;
use Webkernel\Platform\Runtime\Frame;

/** Compiles restricted template nodes into a direct PHP render closure. */
final class TemplateCompiler
{
    /** @param list<TemplateNode> $nodes */
    public function compile(array $nodes): string
    {
        return "<?php declare(strict_types=1);\n"
            .'return static function ('.Frame::class.' $f): string {' . "\n"
            .'    return '.$this->compileNodes($nodes).";\n};\n";
    }

    /** @param list<TemplateNode> $nodes */
    private function compileNodes(array $nodes): string
    {
        $parts = [];
        foreach ($nodes as $node) {
            if ($node instanceof Literal) {
                $parts[] = var_export($node->html, true);
                continue;
            }
            if ($node instanceof Hole) {
                $direct = match ($node->path[0]) {
                    'user' => '$f->user?->'.$node->path[1],
                    'csrfToken' => '$f->csrfToken',
                    default => '$f->value('.var_export($node->path, true).')',
                };
                $parts[] = '$f->e('.$direct.')';
                continue;
            }
            if ($node instanceof AbilityBlock) {
                $mask = Ability::mask($node->abilities);
                $parts[] = "(\$f->allows($mask) ? ".$this->compileNodes($node->children)." : '')";
                continue;
            }
            throw new InvalidArgumentException('Unsupported compiled template node: '.get_debug_type($node));
        }

        return $parts === [] ? "''" : implode(' . ', $parts);
    }

    /** Writes a compiled closure to an opcache-friendly PHP file using atomic replacement. */
    /** @param list<TemplateNode> $nodes */
    public function publish(array $nodes, string $path): void
    {
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create compiled template directory: '.$directory);
        }
        $temporary_path = $path.'.'.bin2hex(random_bytes(8)).'.tmp';
        $source = $this->compile($nodes);
        if (file_put_contents($temporary_path, $source, LOCK_EX) !== strlen($source)) {
            throw new RuntimeException('Unable to write compiled template: '.$temporary_path);
        }
        if (! rename($temporary_path, $path)) {
            unlink($temporary_path);
            throw new RuntimeException('Unable to publish compiled template: '.$path);
        }
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }
}
