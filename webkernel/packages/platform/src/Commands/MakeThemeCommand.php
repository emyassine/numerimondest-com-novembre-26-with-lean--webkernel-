<?php

namespace Webkernel\Platform\Commands;


use function console;
use Composer\InstalledVersions;
use Webkernel\Platform\Support\Commands\Concerns\CanManipulateFiles;
use Webkernel\Platform\Support\Commands\Concerns\HasPanel;
use Webkernel\Platform\Support\Commands\Exceptions\FailureCommandOutput;
use Webkernel\Platform\Support\Commands\Exceptions\SuccessCommandOutput;
use \Webkernel\Console\Command;
use Webkernel\Filesystem\Filesystem;
use Webkernel\Utilities\Arr;
use Webkernel\Utilities\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'make:platform-theme', aliases: [
    'platform:make-theme',
    'platform:theme',
])]
class MakeThemeCommand extends Command
{
    use CanManipulateFiles;
    use HasPanel;

    protected $description = 'Create a new Platform panel theme';

    protected $name = 'make:platform-theme';

    protected string $pm;

    protected Filesystem $filesystem;

    protected string $themePath;

    /**
     * @var array<string>
     */
    protected $aliases = [
        'platform:make-theme',
        'platform:theme',
    ];

    /**
     * @return array<InputArgument>
     */
    protected function getArguments(): array
    {
        return [
            new InputArgument(
                name: 'panel',
                mode: InputArgument::OPTIONAL,
                description: 'The ID of the panel to create the theme for',
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
                name: 'panel',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The panel to create the resource in',
            ),
            new InputOption(
                name: 'pm',
                mode: InputOption::VALUE_REQUIRED,
                description: 'The package manager to use (npm, yarn)',
            ),
            new InputOption(
                name: 'force',
                shortcut: 'F',
                mode: InputOption::VALUE_NONE,
                description: 'Overwrite the contents of the files if they already exist',
            ),
        ];
    }

    public function handle(Filesystem $filesystem): int
    {
        $this->filesystem = $filesystem;

        try {
            $this->configurePanel(question: 'Which panel would you like to create this theme for?');
            $this->configurePackageManager();

            $this->themePath = "resources/css/platform/{$this->panel->getId()}/theme.css";

            $this->checkBuildDependencies();
            $this->createThemeSourceFiles();

            $this->abortIfNotVite();
        } catch (FailureCommandOutput) {
            return static::FAILURE;
        } catch (SuccessCommandOutput) {
            return static::SUCCESS;
        }

        $pendingActions = [];

        // Try to register in `vite.config.js`
        if (! $this->registerInViteConfig()) {
            $pendingActions[] = "Add `{$this->themePath}` to the Vite build input in `vite.config.js`.";
        }

        // Try to register in panel provider
        if (! $this->registerInPanelProvider()) {
            $pendingActions[] = "Register the theme in the {$this->panel->getId()} panel provider using `->viteTheme('{$this->themePath}')`";
        }

        // Show pending manual actions if any
        if (count($pendingActions) > 0) {
            $this->components->warn('Action is required to complete the theme setup:');
            $this->components->bulletList($pendingActions);
            $this->newLine();
        }

        // Offer to compile the theme
        if (console()->confirm('Would you like to compile the theme now?', default: true)) {
            $this->components->info('Compiling theme...');

            $process = new Process([$this->pm, 'run', 'build']);
            $process->setTty(Process::isTtySupported());
            $process->run(function (string $type, string $buffer): void {
                $this->output->write($buffer);
            });
        } else {
            $this->components->info("Run `{$this->pm} run build` to compile the theme.");
        }

        return static::SUCCESS;
    }

    protected function configurePackageManager(): void
    {
        $pmOption = $this->option('pm');
        $this->pm = $pmOption ?? 'npm';

        $process = new Process([$this->pm, '-v']);
        $process->run();

        if (! $process->isSuccessful()) {
            if (filled($pmOption)) {
                $this->error("The [{$pmOption}] package manager is not installed. Please install it before continuing.");
            } else {
                $this->error('Node.js is not installed. Please install before continuing.');
            }

            throw new FailureCommandOutput;
        }

        $this->info("Using {$this->pm} v" . trim($process->getOutput()));
    }

    protected function checkBuildDependencies(): void
    {
        $packageJsonPath = base_path('package.json');

        if (! $this->filesystem->exists($packageJsonPath)) {
            $this->components->warn('No package.json was found. Add Tailwind CSS and its Vite plugin before compiling this theme.');

            return;
        }

        $manifest = json_decode($this->filesystem->get($packageJsonPath), true);

        if (! is_array($manifest)) {
            $this->components->warn('The package.json file is invalid; check its JSON syntax before compiling this theme.');

            return;
        }

        $dependencies = [...($manifest['dependencies'] ?? []), ...($manifest['devDependencies'] ?? [])];
        $missing = array_diff(['tailwindcss', '@tailwindcss/vite'], array_keys($dependencies));

        if ($missing !== []) {
            $this->components->warn('Add the missing build dependencies before compiling this theme: ' . implode(', ', $missing));
        }
    }

    protected function createThemeSourceFiles(): void
    {
        $cssFilePath = resource_path("css/platform/{$this->panel->getId()}/theme.css");

        if (! $this->option('force') && $this->checkForCollision([
            $cssFilePath,
        ])) {
            throw new FailureCommandOutput;
        }

        $classDirectory = (string) str(Arr::first($this->panel->getPageDirectories()))
            ->afterLast('Platform/')
            ->beforeLast('Pages');

        $viewDirectory = str($classDirectory)
            ->explode('/')
            ->map(fn ($segment) => Str::lower(Str::kebab($segment)))
            ->implode('/');

        $this->copyStubToApp('ThemeCss', $cssFilePath, [
            'classDirectory' => filled($classDirectory) ? $classDirectory : '',
            'platformThemeCssPath' => $this->escapeCssString(
                $this->getRelativePath(
                    (string) InstalledVersions::getInstallPath('webkernel/platform') . '/resources/css/theme.css',
                    dirname($cssFilePath),
                ),
            ),
            'panel' => $this->panel->getId(),
            'viewDirectory' => filled($viewDirectory) ? $viewDirectory : '',
        ]);

        $this->components->info("Platform theme [resources/css/platform/{$this->panel->getId()}/theme.css] created successfully.");
    }

    protected function abortIfNotVite(): void
    {
        if (glob(base_path('vite.config.*s'))) {
            return;
        }

        $panelId = $this->panel->getId();
        $publicPath = "public/css/platform/{$panelId}/theme.css";

        $this->components->warn('Action is required to complete the theme setup:');
        $this->components->bulletList([
            "It looks like you don't have Vite installed. Please use your asset bundling system of choice to compile `{$this->themePath}` into `{$publicPath}`.",
            "If you're not currently using a bundler, we recommend using Vite. Alternatively, you can use the Tailwind CLI with the following command:",
            "npx @tailwindcss/cli --input ./{$this->themePath} --output ./{$publicPath} --minify",
            "Make sure to register the theme in the {$panelId} panel provider using `->theme(asset('css/platform/{$panelId}/theme.css'))`",
        ]);

        throw new SuccessCommandOutput;
    }

    protected function registerInViteConfig(): bool
    {
        $viteConfigPath = base_path('vite.config.js');

        if (! $this->filesystem->exists($viteConfigPath)) {
            return false;
        }

        $contents = $this->filesystem->get($viteConfigPath);

        // Check if already registered
        if (str_contains($contents, $this->themePath)) {
            $this->components->info('Theme already registered in vite.config.js.');

            return true;
        }

        // Webkernel's Vite configuration uses Rollup's named input object.
        $pattern = '/(\binput\s*:\s*\{)([^}]*)(\})/s';

        if (! preg_match($pattern, $contents, $matches)) {
            return false;
        }

        $inputArrayContents = $matches[2];

        // Require a recognizable existing application input before editing.
        if (! preg_match('/[\'"](?:app\/)?resources\/(?:css|js)\//', $inputArrayContents)) {
            return false;
        }

        // Detect quote style from existing entries
        $quoteStyle = str_contains($inputArrayContents, "'") ? "'" : '"';

        // Add a stable key/value entry to the named input object.
        if (! preg_match('/^(.*?)(\s*)$/s', $inputArrayContents, $lastEntryMatch)) {
            return false;
        }

        $beforeTrailing = rtrim($inputArrayContents);
        $entryName = 'theme_' . preg_replace('/[^a-zA-Z0-9_$]/', '_', $this->panel->getId());
        $newEntry = "{$entryName}: {$quoteStyle}{$this->themePath}{$quoteStyle},";
        $newInputArrayContents = $beforeTrailing . "\n                {$newEntry}\n            ";

        $newContents = preg_replace(
            $pattern,
            '$1' . str_replace(['\\', '$'], ['\\\\', '\\$'], $newInputArrayContents) . '$3',
            $contents,
            1
        );

        if ($newContents === null || $newContents === $contents) {
            return false;
        }

        $this->filesystem->put($viteConfigPath, $newContents);
        $this->components->info('Added theme to vite.config.js input array.');

        return true;
    }

    protected function registerInPanelProvider(): bool
    {
        $panelId = $this->panel->getId();

        // Find the panel provider file
        $providerPath = app_path('Providers/Platform/' . Str::studly($panelId) . 'PanelProvider.php');

        if (! $this->filesystem->exists($providerPath)) {
            return false;
        }

        $contents = $this->filesystem->get($providerPath);

        // Check if already registered
        if (str_contains($contents, 'viteTheme(')) {
            $this->components->info('viteTheme() already configured in panel provider.');

            return true;
        }

        // Look for ->id('panelId') to confirm we're in the right file
        $idPattern = '/(->id\s*\(\s*[\'"]' . preg_quote($panelId, '/') . '[\'"]\s*\))(\s*\n)/';
        if (! preg_match($idPattern, $contents)) {
            return false;
        }

        // Try to insert after ->path() first, then fall back to ->id()
        $pathPattern = '/(->path\s*\(\s*[\'"][^\'"]*[\'"]\s*\))(\s*\n)/';

        if (preg_match($pathPattern, $contents)) {
            $pattern = $pathPattern;
        } else {
            // No ->path() found, insert after ->id() instead
            $pattern = $idPattern;
        }

        $replacement = '$1' . "\n            ->viteTheme('{$this->themePath}')" . '$2';
        $newContents = preg_replace($pattern, $replacement, $contents, 1);

        if ($newContents === null || $newContents === $contents) {
            return false;
        }

        $this->filesystem->put($providerPath, $newContents);
        $this->components->info('Added viteTheme() to panel provider.');

        return true;
    }
}
