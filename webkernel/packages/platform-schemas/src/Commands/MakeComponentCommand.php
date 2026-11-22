<?php

namespace Webkernel\Platform\Schemas\Commands;


use function console;
use Webkernel\Platform\Schemas\Commands\FileGenerators\ComponentClassGenerator;
use Webkernel\Platform\Support\Commands\Concerns\CanAskForComponentLocation;
use Webkernel\Platform\Support\Commands\Concerns\CanAskForViewLocation;
use Webkernel\Platform\Support\Commands\Concerns\CanManipulateFiles;
use Webkernel\Platform\Support\Commands\Exceptions\FailureCommandOutput;
use \Webkernel\Console\Command;
use Webkernel\Utilities\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:platform-schema-component', aliases: [
    'platform:component',
    'platform:layout',
    'platform:form-layout',
    'platform:infolist-layout',
    'platform:schema-component',
    'platform:schema-layout',
    'forms:layout',
    'forms:make-layout',
    'infolists:layout',
    'infolists:make-layout',
    'make:platform-layout',
    'make:platform-schema-layout',
    'make:infolist-layout',
    'make:form-layout',
])]
class MakeComponentCommand extends Command
{
    use CanAskForComponentLocation;
    use CanAskForViewLocation;
    use CanManipulateFiles;

    protected $description = 'Create a new schema component class and view';

    protected $name = 'make:platform-schema-component';

    protected string $fqnEnd;

    protected string $fqn;

    protected string $path;

    protected string $view;

    protected string $viewPath;

    /**
     * @var array<string>
     */
    protected $aliases = [
        'platform:component',
        'platform:layout',
        'platform:form-layout',
        'platform:infolist-layout',
        'platform:schema-component',
        'platform:schema-layout',
        'forms:layout',
        'forms:make-layout',
        'infolists:layout',
        'infolists:make-layout',
        'make:platform-layout',
        'make:platform-schema-layout',
        'make:infolist-layout',
        'make:form-layout',
    ];

    /**
     * @return array<InputArgument>
     */
    protected function getArguments(): array
    {
        return [
            new InputArgument(
                name: 'name',
                mode: InputArgument::OPTIONAL,
                description: 'The name of the component to generate, optionally prefixed with directories',
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

            $this->createComponent();
            $this->createView();
        } catch (FailureCommandOutput) {
            return static::FAILURE;
        }

        $this->components->info("Platform component [{$this->fqn}] created successfully.");

        return static::SUCCESS;
    }

    protected function configureFqnEnd(): void
    {
        $this->fqnEnd = (string) str($this->argument('name') ?? console()->text(
            label: 'What is the component name?',
            placeholder: 'InlineSection',
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
            path: 'Schemas/Components',
            question: 'Where would you like to create the component?',
        );

        $this->fqn = "{$namespace}\\{$this->fqnEnd}";
        $this->path = (string) str("{$path}\\{$this->fqnEnd}.php")
            ->replace('\\', '/')
            ->replace('//', '/');

        [
            $this->view,
            $this->viewPath,
        ] = $this->askForViewLocation(
            str($this->fqn)
                ->afterLast('\\Schemas\\Components\\')
                ->prepend('Webkernel\Platform\\Schemas\\Components\\')
                ->replace('\\', '/')
                ->explode('/')
                ->map(Str::kebab(...))
                ->implode('.'),
            defaultNamespace: $viewNamespace,
        );
    }

    protected function createComponent(): void
    {
        if (! $this->option('force') && $this->checkForCollision($this->path)) {
            throw new FailureCommandOutput;
        }

        $this->writeFile($this->path, app(ComponentClassGenerator::class, [
            'fqn' => $this->fqn,
            'view' => $this->view,
        ]));
    }

    protected function createView(): void
    {
        if (blank($this->view)) {
            return;
        }

        if (! $this->option('force') && $this->checkForCollision($this->viewPath)) {
            throw new FailureCommandOutput;
        }

        $this->copyStubToApp('ComponentView', $this->viewPath);
    }
}
