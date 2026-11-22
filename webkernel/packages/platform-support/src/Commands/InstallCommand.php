<?php

namespace Webkernel\Platform\Support\Commands;


use function console;
use Composer\InstalledVersions;
use Webkernel\Platform\PanelProvider;
use Webkernel\Platform\Support\Commands\Concerns\CanGeneratePanels;
use Webkernel\Platform\Support\Commands\Concerns\CanManipulateFiles;
use Webkernel\Platform\Support\Commands\Exceptions\FailureCommandOutput;
use \Webkernel\Console\Command;
use Webkernel\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'platform:install', aliases: [
    'install:platform',
])]
class InstallCommand extends Command
{
    use CanGeneratePanels;
    use CanManipulateFiles;

    protected $description = 'Install Platform';

    protected $name = 'platform:install';

    /**
     * @var array<string>
     */
    protected $aliases = [
        'install:platform',
    ];

    /**
     * @return array<InputOption>
     */
    protected function getOptions(): array
    {
        return [
            new InputOption(
                name: 'panels',
                shortcut: null,
                mode: InputOption::VALUE_NONE,
                description: 'Install the panel builder and create the first panel',
            ),
            new InputOption(
                name: 'scaffold',
                shortcut: null,
                mode: InputOption::VALUE_NONE,
                description: 'Install Platform packages for use from application views or Liveview components',
            ),
            new InputOption(
                name: 'notifications',
                shortcut: null,
                mode: InputOption::VALUE_NONE,
                description: 'Install the Platform flash notifications into the scaffolded layout file',
            ),
            new InputOption(
                name: 'force',
                shortcut: 'F',
                mode: InputOption::VALUE_NONE,
                description: 'Overwrite the contents of the files if they already exist',
            ),
        ];
    }

    public function __invoke(): int
    {
        try {
            $this->installAdminPanel();
            $this->installScaffolding();
            $this->installUpgradeCommand();
            $this->ignorePublishedAssets();
        } catch (FailureCommandOutput) {
            return static::FAILURE;
        }

        $this->call(UpgradeCommand::class);


        return static::SUCCESS;
    }

    protected function installAdminPanel(): void
    {
        if (! $this->option('panels')) {
            return;
        }

        if (! class_exists(PanelProvider::class)) {
            $this->components->error('Please require [webkernel/platform] before attempting to install the Panel Builder.');

            throw new FailureCommandOutput;
        }

        $this->generatePanel(defaultId: 'admin', isForced: $this->option('force'));
    }

    protected function installScaffolding(): void
    {
        if (! $this->option('scaffold')) {
            return;
        }

        $filesystem = app(Filesystem::class);
        $filesystem->copyDirectory(__DIR__ . '/../../stubs/scaffolding', base_path());

        $hasNotifications = false;

        if (
            InstalledVersions::isInstalled('webkernel/platform-notifications') &&
            ($this->option('notifications') || console()->confirm(
                label: 'Do you want to send flash notifications using Platform?',
                default: true,
            ))
        ) {
            $layout = $filesystem->get(resource_path('views/layouts/app.view.php'));
            $layout = (string) str($layout)
                ->replace('{{ $slot }}', '{{ $slot }}' . PHP_EOL . PHP_EOL . '        @liveview(\'notifications\')');
            $filesystem->put(resource_path('views/layouts/app.view.php'), $layout);

            $hasNotifications = true;
        }

        $cssDirectory = resource_path('css');
        $packagesCssImports = collect([
            'webkernel/platform-support',
            'webkernel/platform-actions',
            'webkernel/platform-forms',
            'webkernel/platform-infolists',
            ...($hasNotifications ? ['webkernel/platform-notifications'] : []),
            'webkernel/platform-schemas',
            'webkernel/platform-tables',
            'webkernel/platform-widgets',
        ])
            ->filter(fn (string $package): bool => InstalledVersions::isInstalled($package))
            ->map(function (string $package) use ($cssDirectory): string {
                $packageCssPath = $this->getRelativePath(
                    (string) InstalledVersions::getInstallPath($package) . '/resources/css/index.css',
                    $cssDirectory,
                );

                return "@import '{$this->escapeCssString($packageCssPath)}';";
            })
            ->implode(PHP_EOL);

        $css = $filesystem->get(resource_path('css/app.css'));
        $css = (string) str($css)
            ->replace('{{ platformCssImports }}', $packagesCssImports)
            ->replace("@source '{{ laravelPaginationViewsPath }}/*.view.php';" . PHP_EOL, '');
        $filesystem->put(resource_path('css/app.css'), $css);

        $this->components->info('Scaffolding installed successfully.');

        $this->components->info('Please run `npm run build` to compile your new assets.');
    }

    protected function installUpgradeCommand(): void
    {
        $path = base_path('composer.json');

        if (! file_exists($path)) {
            return;
        }

        $configuration = json_decode(file_get_contents($path), associative: true);

        if (! is_array($configuration)) {
            $this->components->warn('Could not update [composer.json] because it could not be parsed as JSON. Add "@php platform platform:upgrade" to the "post-autoload-dump" scripts manually.');

            return;
        }

        $command = '@php platform platform:upgrade';

        if (in_array($command, $configuration['scripts']['post-autoload-dump'] ?? [])) {
            return;
        }

        $configuration['scripts']['post-autoload-dump'] ??= [];
        $configuration['scripts']['post-autoload-dump'][] = $command;

        file_put_contents(
            $path,
            (string) str(json_encode($configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                ->append(PHP_EOL)
                ->replace(
                    search: "    \"keywords\": [\n        \"webkernel\",\n        \"framework\"\n    ],",
                    replace: '    "keywords": ["webkernel", "framework"],',
                )
                ->replace(
                    search: "    \"keywords\": [\n        \"framework\",\n        \"webkernel\"\n    ],",
                    replace: '    "keywords": ["framework", "webkernel"],',
                ),
        );
    }

    protected function ignorePublishedAssets(): void
    {
        $path = base_path('.gitignore');

        if (! file_exists($path)) {
            return;
        }

        $contents = file_get_contents($path);

        preg_match('/\r\n|\n|\r/', $contents, $lineEndingMatches);

        $lineEnding = $lineEndingMatches[0] ?? PHP_EOL;

        $lines = preg_split('/\r\n|\n|\r/', $contents);

        $existingRules = collect($lines)
            ->map(fn (string $rule): string => trim(rtrim($rule), '/'))
            ->all();

        $assetsPath = trim((string) config('platform.assets_path'), '/');

        $newRules = collect(['css', 'fonts', 'js'])
            ->map(fn (string $directory): string => collect(['public', $assetsPath, $directory, 'platform'])
                ->filter()
                ->implode('/'))
            ->reject(fn (string $rule): bool => in_array($rule, $existingRules))
            ->all();

        if (blank($newRules)) {
            return;
        }

        foreach ($newRules as $newRule) {
            $previousRule = null;
            $previousRuleIndex = null;

            foreach ($lines as $lineIndex => $line) {
                $rule = trim(rtrim($line), '/');

                if (
                    (! str_starts_with($rule, 'public/')) ||
                    (strcmp($rule, $newRule) >= 0) ||
                    (($previousRule !== null) && (strcmp($rule, $previousRule) <= 0))
                ) {
                    continue;
                }

                $previousRule = $rule;
                $previousRuleIndex = $lineIndex;
            }

            if ($previousRuleIndex !== null) {
                array_splice($lines, $previousRuleIndex + 1, 0, ["/{$newRule}"]);

                continue;
            }

            while (($lines !== []) && (end($lines) === '')) {
                array_pop($lines);
            }

            if ($lines !== []) {
                $lines[] = '';
            }

            $lines[] = "/{$newRule}";
        }

        if (end($lines) !== '') {
            $lines[] = '';
        }

        file_put_contents(
            $path,
            implode($lineEnding, $lines),
        );

        $this->components->info('Added the published Platform assets to [.gitignore].');
    }

}
