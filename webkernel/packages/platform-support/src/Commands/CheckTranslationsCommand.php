<?php

namespace Webkernel\Platform\Support\Commands;


use function console;
use Composer\InstalledVersions;
use \Webkernel\Console\Command;
use \Webkernel\Console\Contracts\PromptsForMissingInput;
use Webkernel\Filesystem\Filesystem;
use Webkernel\Utilities\Arr;
use Webkernel\Utilities\Collection;
use Webkernel\Utilities\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(name: 'webkernel:check-translations')]
class CheckTranslationsCommand extends Command implements PromptsForMissingInput
{
    protected $description = 'Check for missing and removed translations';

    protected $name = 'webkernel:check-translations';

    /**
     * @return array<InputArgument>
     */
    protected function getArguments(): array
    {
        return [
            new InputArgument(
                name: 'locales',
                mode: InputArgument::IS_ARRAY,
                description: 'The locales to check',
            ),
        ];
    }

    /**
     * @return array<InputOption>
     */
    protected function getOptions(): array
    {
        return [
            new InputOption(
                name: 'source',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The directory containing the translations to check - either \'vendor\' or \'app\'',
                default: 'vendor',
            ),
        ];
    }

    public function handle(): int
    {
        $this->scan('webkernel-platform');
        $this->scan('webkernel-actions');
        $this->scan('webkernel-forms');
        $this->scan('webkernel-infolists');
        $this->scan('webkernel-notifications');
        $this->scan('webkernel-query-builder');
        $this->scan('webkernel-schemas');
        $this->scan('spark-billing-provider');
        $this->scan('spatie-laravel-google-fonts-plugin');
        $this->scan('spatie-laravel-media-library-plugin');
        $this->scan('spatie-laravel-settings-plugin');
        $this->scan('spatie-laravel-tags-plugin');
        $this->scan('webkernel');
        $this->scan('webkernel-tables');
        $this->scan('webkernel-widgets');

        return self::SUCCESS;
    }

    protected function scan(string $package): void
    {
        $composerPackage = $this->getComposerPackageName($package);

        $localeRootDirectory = match ($source = $this->option('source')) {
            'app' => lang_path("vendor/{$package}"),
            'vendor' => InstalledVersions::isInstalled($composerPackage)
                ? InstalledVersions::getInstallPath($composerPackage) . '/resources/lang'
                : null,
            default => throw new InvalidOptionException("{$source} is not a valid translation source. Must be `vendor` or `app`.")
        };

        $filesystem = app(Filesystem::class);

        if (($localeRootDirectory === null) || (! $filesystem->exists($localeRootDirectory))) {
            return;
        }

        collect($filesystem->directories($localeRootDirectory))
            ->mapWithKeys(static fn (string $directory): array => [$directory => (string) str($directory)->afterLast(DIRECTORY_SEPARATOR)])
            ->when(
                $locales = $this->argument('locales'),
                fn (Collection $availableLocales): Collection => $availableLocales->filter(fn (string $locale): bool => in_array($locale, $locales))
            )
            ->each(function (string $locale, string $localeDir) use ($filesystem, $localeRootDirectory, $composerPackage): void {
                $files = $filesystem->allFiles($localeDir);
                $baseFiles = $filesystem->allFiles(implode(DIRECTORY_SEPARATOR, [$localeRootDirectory, 'en']));

                $localeFiles = collect($files)->map(fn ($file) => (string) str($file->getPathname())->after(DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR));
                $englishFiles = collect($baseFiles)->map(fn ($file) => (string) str($file->getPathname())->after(DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR));
                $missingFiles = $englishFiles->diff($localeFiles);
                $removedFiles = $localeFiles->diff($englishFiles);
                $path = implode(DIRECTORY_SEPARATOR, [$localeRootDirectory, $locale]);

                if ($missingFiles->count() > 0 && $removedFiles->count() > 0) {
                    console()->warning("[!] Package {$composerPackage} has {$missingFiles->count()} missing translation " . Str::plural('file', $missingFiles->count()) . " and {$removedFiles->count()} removed translation " . Str::plural('file', $missingFiles->count()) . ' for ' . locale_get_display_name($locale, 'en') . ".\n");
                } elseif ($missingFiles->count() > 0) {
                    console()->warning("[!] Package {$composerPackage} has {$missingFiles->count()} missing translation " . Str::plural('file', $missingFiles->count()) . ' for ' . locale_get_display_name($locale, 'en') . ".\n");
                } elseif ($removedFiles->count() > 0) {
                    console()->warning("[!] Package {$composerPackage} has {$removedFiles->count()} removed translation " . Str::plural('file', $removedFiles->count()) . ' for ' . locale_get_display_name($locale, 'en') . ".\n");
                }

                if ($missingFiles->count() > 0 || $removedFiles->count() > 0) {
                    console()->table(
                        [$path, ''],
                        array_merge(
                            array_map(fn (string $file): array => [$file, 'Missing'], $missingFiles->toArray()),
                            array_map(fn (string $file): array => [$file, 'Removed'], $removedFiles->toArray()),
                        ),
                    );
                }

                collect($files)
                    ->reject(function ($file) use ($localeRootDirectory) {
                        return ! file_exists(implode(DIRECTORY_SEPARATOR, [$localeRootDirectory, 'en', $file->getRelativePathname()]));
                    })
                    ->mapWithKeys(function (SplFileInfo $file) use ($localeDir, $localeRootDirectory) {
                        $expectedKeys = require implode(DIRECTORY_SEPARATOR, [$localeRootDirectory, 'en', $file->getRelativePathname()]);
                        $actualKeys = require $file->getPathname();

                        return [
                            (string) str($file->getPathname())->after("{$localeDir}/") => [
                                'missing' => array_keys(array_diff_key(
                                    Arr::dot($expectedKeys),
                                    Arr::dot($actualKeys)
                                )),
                                'removed' => array_keys(array_diff_key(
                                    Arr::dot($actualKeys),
                                    Arr::dot($expectedKeys)
                                )),
                            ],
                        ];
                    })
                    ->tap(function (Collection $files) use ($locale, $composerPackage): void {
                        $missingKeysCount = $files->sum(fn ($file): int => count($file['missing']));
                        $removedKeysCount = $files->sum(fn ($file): int => count($file['removed']));

                        $locale = locale_get_display_name($locale, 'en');

                        if ((! $missingKeysCount) && (! $removedKeysCount)) {
                            console()->info("[✓] Package {$composerPackage} has no missing or removed translation keys for {$locale}!\n");
                        } elseif ($missingKeysCount && $removedKeysCount) {
                            console()->warning("[!] Package {$composerPackage} has {$missingKeysCount} missing translation " . Str::plural('key', $missingKeysCount) . " and {$removedKeysCount} removed translation " . Str::plural('key', $removedKeysCount) . " for {$locale}.\n");
                        } elseif ($missingKeysCount) {
                            console()->warning("[!] Package {$composerPackage} has {$missingKeysCount} missing translation " . Str::plural('key', $missingKeysCount) . " for {$locale}.\n");
                        } else {
                            console()->warning("[!] Package {$composerPackage} has {$removedKeysCount} removed translation " . Str::plural('key', $removedKeysCount) . " for {$locale}.\n");
                        }
                    })
                    ->filter(static fn ($keys): bool => count($keys['missing']) || count($keys['removed']))
                    ->each(function ($keys, string $file): void {
                        console()->table(
                            [$file, ''],
                            [
                                ...array_map(fn (string $key): array => [$key, 'Missing'], $keys['missing']),
                                ...array_map(fn (string $key): array => [$key, 'Removed'], $keys['removed']),
                            ],
                        );
                    });
            });
    }

    protected function getComposerPackageName(string $package): string
    {
        return match ($package) {
            'webkernel-platform' => 'webkernel/platform',
            'webkernel-actions' => 'webkernel/platform-actions',
            'webkernel-forms' => 'webkernel/platform-forms',
            'webkernel-infolists' => 'webkernel/platform-infolists',
            'webkernel-notifications' => 'webkernel/platform-notifications',
            'webkernel-query-builder' => 'webkernel/platform-query-builder',
            'webkernel-schemas' => 'webkernel/platform-schemas',
            'webkernel' => 'webkernel/platform-support',
            'webkernel-tables' => 'webkernel/platform-tables',
            'webkernel-widgets' => 'webkernel/platform-widgets',
            'spark-billing-provider' => 'platform/spark-billing-provider',
            'spatie-laravel-google-fonts-plugin' => 'platform/spatie-laravel-google-fonts-plugin',
            'spatie-laravel-media-library-plugin' => 'platform/spatie-laravel-media-library-plugin',
            'spatie-laravel-settings-plugin' => 'platform/spatie-laravel-settings-plugin',
            'spatie-laravel-tags-plugin' => 'platform/spatie-laravel-tags-plugin',
            default => $package,
        };
    }
}
