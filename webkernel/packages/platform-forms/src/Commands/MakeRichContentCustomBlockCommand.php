<?php

namespace Webkernel\Platform\Forms\Commands;


use function console;
use Webkernel\Platform\Forms\Commands\FileGenerators\RichContentCustomBlockClassGenerator;
use Webkernel\Platform\Support\Commands\Concerns\CanAskForComponentLocation;
use Webkernel\Platform\Support\Commands\Concerns\CanAskForViewLocation;
use Webkernel\Platform\Support\Commands\Concerns\CanManipulateFiles;
use Webkernel\Platform\Support\Commands\Exceptions\FailureCommandOutput;
use \Webkernel\Console\Command;
use Webkernel\Utilities\Str;
use Webkernel\Utilities\Stringable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:platform-rich-content-custom-block', aliases: [
    'platform:rich-content-custom-block',
    'platform:rich-editor-custom-block',
    'platform:custom-block',
    'forms:rich-content-custom-block',
    'forms:rich-editor-custom-block',
    'forms:make-custom-block',
    'make:platform-rich-editor-custom-block',
    'make:platform-custom-block',
    'make:rich-content-custom-block',
    'make:rich-editor-custom-block',
    'make:custom-block',
])]
class MakeRichContentCustomBlockCommand extends Command
{
    use CanAskForComponentLocation;
    use CanAskForViewLocation;
    use CanManipulateFiles;

    protected $description = 'Create a new rich editor custom block class and view';

    protected $name = 'make:platform-rich-content-custom-block';

    /**
     * @var array<string>
     */
    protected $aliases = [
        'platform:rich-content-custom-block',
        'platform:rich-editor-custom-block',
        'platform:custom-block',
        'forms:rich-content-custom-block',
        'forms:rich-editor-custom-block',
        'forms:make-custom-block',
        'make:platform-rich-editor-custom-block',
        'make:platform-custom-block',
        'make:rich-content-custom-block',
        'make:rich-editor-custom-block',
        'make:custom-block',
    ];

    protected string $fqnEnd;

    protected string $fqn;

    protected string $path;

    protected string $view;

    protected string $previewView;

    protected string $viewPath;

    protected string $previewViewPath;

    /**
     * @return array<InputArgument>
     */
    protected function getArguments(): array
    {
        return [
            new InputArgument(
                name: 'name',
                mode: InputArgument::OPTIONAL,
                description: 'The name of the custom block to generate, optionally prefixed with directories',
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
                name: 'force',
                shortcut: 'F',
                mode: InputOption::VALUE_NONE,
                description: 'Overwrite the contents of the files if they already exist',
            ),
        ];
    }

    public function handle(): int
    {
        try {
            $this->configureFqnEnd();

            $this->configureLocation();

            $this->createCustomBlock();
            $this->createView();
            $this->createPreviewView();
        } catch (FailureCommandOutput) {
            return static::FAILURE;
        }

        $this->components->info("Platform rich editor custom block [{$this->fqn}] created successfully.");

        return static::SUCCESS;
    }

    protected function configureFqnEnd(): void
    {
        $this->fqnEnd = (string) str($this->argument('name') ?? console()->text(
            label: 'What is the custom block name?',
            placeholder: 'HeroBlock',
            required: true,
        ))
            ->trim('/')
            ->trim('\\')
            ->trim(' ')
            ->studly()
            ->replace('/', '\\');
    }

    protected function configureLocation(): void
    {
        [
            $namespace,
            $path,
            $viewNamespace,
        ] = $this->askForComponentLocation(
            path: 'Forms/Components/RichEditor/RichContentCustomBlocks',
            question: 'Where would you like to create the custom block?',
        );

        $this->fqn = "{$namespace}\\{$this->fqnEnd}";
        $this->path = (string) str("{$path}\\{$this->fqnEnd}.php")
            ->replace('\\', '/')
            ->replace('//', '/');

        [
            $view,
            $viewPath,
        ] = $this->askForViewLocation(
            str($this->fqn)
                ->afterLast('\\Forms\\Components\\RichEditor\\RichContentCustomBlocks\\')
                ->prepend('Webkernel\Platform\\Forms\\Components\\RichEditor\\RichContentCustomBlocks\\')
                ->whenEndsWith('Block', fn (Stringable $stringable) => $stringable->beforeLast('Block'))
                ->replace('\\', '/')
                ->explode('/')
                ->map(Str::kebab(...))
                ->implode('.'),
            defaultNamespace: $viewNamespace,
        );
        $this->view = str($view)->append('.index');
        $this->viewPath = str($viewPath)->replaceLast('.view.php', '/index.view.php');
        $this->previewView = str($view)->append('.preview');
        $this->previewViewPath = str($viewPath)->replaceLast('.view.php', '/preview.view.php');
    }

    protected function createCustomBlock(): void
    {
        if (! $this->option('force') && $this->checkForCollision($this->path)) {
            throw new FailureCommandOutput;
        }

        app(RichContentCustomBlockClassGenerator::class, [
            'fqn' => $this->fqn,
            'view' => $this->view,
            'previewView' => $this->previewView,
        ])->generate($this->path);
    }

    protected function createView(): void
    {
        if (blank($this->view)) {
            return;
        }

        if (! $this->option('force') && $this->checkForCollision($this->viewPath)) {
            throw new FailureCommandOutput;
        }

        $this->copyStubToApp('RichContentCustomBlockView', $this->viewPath);
    }

    protected function createPreviewView(): void
    {
        if (blank($this->previewViewPath)) {
            return;
        }

        if (! $this->option('force') && $this->checkForCollision($this->previewViewPath)) {
            throw new FailureCommandOutput;
        }

        $this->copyStubToApp('RichContentCustomBlockPreviewView', $this->previewViewPath);
    }
}
