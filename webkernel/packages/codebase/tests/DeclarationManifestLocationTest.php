<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

use PHPUnit\Framework\TestCase;
use Webkernel\Getters\GetWebkernelComponentCatalog;

final class DeclarationManifestLocationTest extends TestCase
{
    public function testWebkernelArtifactsArePublishedOutsideComposerMetadata(): void
    {
        self::assertSame([], glob(vendor_path('composer/webkernel_*.php')));
        self::assertFileExists(vendor_path('composer/autoload_classmap.php'));
        self::assertArrayHasKey('webkernel/codebase', webkernel_packages());
        self::assertNotEmpty(require base_path('runtime/cache/declarations/webkernel_bindings.php'));
    }

    public function testRuntimeCatalogPagesReconstructThePublishedInventory(): void
    {
        $catalog = GetWebkernelComponentCatalog::byNamespace();
        $pages = [];
        for ($page = 1; $page <= GetWebkernelComponentCatalog::pageCount(); $page++) {
            foreach (GetWebkernelComponentCatalog::page($page) as $namespace => $names) {
                $pages[$namespace] = [...($pages[$namespace] ?? []), ...$names];
            }
        }

        self::assertSame($catalog, $pages);
        self::assertSame(
            GetWebkernelComponentCatalog::count(),
            array_sum(array_map(count(...), $pages)),
        );
    }
}
