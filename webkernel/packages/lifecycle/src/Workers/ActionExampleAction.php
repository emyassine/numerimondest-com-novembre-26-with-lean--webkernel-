<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Workers;

use RuntimeException;
use Webkernel\Lifecycle\StandardLCAction;
use Webkernel\Lifecycle\StandardComposerIO;

/**
 * Example action that writes a deterministic artifact to the lifecycle package.
 */
final class ActionExampleAction extends StandardLCAction
{
    /** @return string Label shown when the example action runs. */
    public function name(): string
    {
        return 'Generate lifecycle example artifact';
    }

    /**
     * Writes the example artifact to the installed lifecycle package.
     * @throws RuntimeException If the artifact directory or file cannot be written.
     */
    protected function run(): void
    {
        $generated_dir = webkernel_package('lifecycle', 'generated');

        if (! is_dir($generated_dir) && ! mkdir($generated_dir, 0775, true) && ! is_dir($generated_dir)) {
            throw new RuntimeException(sprintf('Unable to create generated directory: %s', $generated_dir));
        }

        $output_path = $generated_dir . '/lifecycle-example.txt';
        $written     = file_put_contents($output_path, "Lifecycle example action completed.\n", LOCK_EX);

        if ($written === false) {
            throw new RuntimeException(sprintf('Unable to write lifecycle example artifact: %s', $output_path));
        }

        $relative_path = ltrim(str_replace(webkernel_package('lifecycle'), '', $output_path), '/\\');

        StandardComposerIO::write(sprintf('Wrote artifact <fg=gray>%s</>', $relative_path));
    }
}
