<?php

namespace Webkernel\Platform\Support\Commands;

use Webkernel\Platform\Support\Commands\Concerns\CanManipulateFiles;
use Webkernel\Platform\Support\Facades\PlatformAsset;
use \Webkernel\Console\Command;
use Webkernel\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'platform:assets')]
class AssetsCommand extends Command
{
    use CanManipulateFiles;

    protected $description = 'Set up Platform assets';

    protected $name = 'platform:assets';

    /** @var array<string> */
    protected array $publishedAssets = [];

    public function handle(Filesystem $filesystem): int
    {
        foreach (PlatformAsset::getAlpineComponents() as $asset) {
            if ($asset->isRemote()) {
                continue;
            }

            $this->copyAsset($asset->getPath(), $asset->getPublicPath());
        }

        foreach (PlatformAsset::getFonts() as $asset) {
            $assetPublicPath = $asset->getPublicPath();

            foreach ($filesystem->allFiles($asset->getPath()) as $file) {
                if ($file->getExtension() === 'js') {
                    continue;
                }

                $this->copyAsset($file->getPathname(), $assetPublicPath . DIRECTORY_SEPARATOR . $file->getFilename());
            }
        }

        foreach (PlatformAsset::getScripts() as $asset) {
            if ($asset->isRemote()) {
                continue;
            }

            $this->copyAsset($asset->getPath(), $asset->getPublicPath());
        }

        foreach (PlatformAsset::getStyles() as $asset) {
            if ($asset->isRemote()) {
                continue;
            }

            $this->copyAsset($asset->getPath(), $asset->getPublicPath());
        }

        foreach (PlatformAsset::getThemes() as $asset) {
            if ($asset->isRemote()) {
                continue;
            }

            $this->copyAsset($asset->getPath(), $asset->getPublicPath());
        }

        $this->components->bulletList($this->publishedAssets);

        $this->components->info('Successfully published assets!');

        return static::SUCCESS;
    }

    protected function copyAsset(string $from, string $to): void
    {
        $filesystem = app(Filesystem::class);

        [$from, $to] = str_replace('/', DIRECTORY_SEPARATOR, [$from, $to]);

        $filesystem->ensureDirectoryExists(
            (string) str($to)
                ->beforeLast(DIRECTORY_SEPARATOR),
        );

        $filesystem->replace($to, $filesystem->get($from), 0666 & ~umask());

        $this->publishedAssets[] = $to;
    }
}
