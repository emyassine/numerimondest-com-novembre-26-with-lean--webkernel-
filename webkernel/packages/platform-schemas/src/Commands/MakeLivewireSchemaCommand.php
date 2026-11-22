<?php

namespace Webkernel\Platform\Schemas\Commands;


use function console;
use Webkernel\Platform\Schemas\Commands\FileGenerators\LivewireSchemaComponentClassGenerator;
use Webkernel\Platform\Support\Commands\Concerns\CanAskForLivewireComponentLocation;
use Webkernel\Platform\Support\Commands\Concerns\CanAskForViewLocation;
use Webkernel\Platform\Support\Commands\Concerns\CanManipulateFiles;
use Webkernel\Platform\Support\Commands\Exceptions\FailureCommandOutput;
use \Webkernel\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:platform-livewire-schema', aliases: [
    'platform:infolist-schema',
    'platform:livewire-schema',
    'make:platform-infolist-schema',
    'make:infolist-schema',
    'make:livewire-schema',
])]
class MakeLivewireSchemaCommand extends Command
{
    use CanAskForLivewireComponentLocation;
    use CanAskForViewLocation;
    use CanManipulateFiles;

    protected $description = 'Create a new Livewire component containing a Platform schema';

    protected $name = 'make:platform-livewire-schema';

    /**
     * @var array<string>
     */
    protected $aliases = [
        'platform:infolist-schema',
        'platform:livewire-schema',
        'make:platform-infolist-schema',
        'make:infolist-schema',
        'make:livewire-schema',
    ];

    /**
     * @var class-string
     */
    protected string $fqn;

    protected string $fqnEnd;

    protected string $path;

    protected ?string $view = null;

    protected ?string $viewPath = null;

    /**
     * @return array<InputArgument>
     */
    protected function getArguments(): array
    {
        return [
            new InputArgument(
                name: 'name',
                mode: InputArgument::OPTIONAL,
                description: 'The name of the Livewire component to generate, optionally prefixed with directories',
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

            $this->createLivewireComponent();
            $this->createView();
        } catch (FailureCommandOutput) {
            return static::FAILURE;
        }

        $this->components->info("Livewire component [{$this->fqn}] created successfully.");

        return static::SUCCESS;
    }

    protected function configureFqnEnd(): void
    {
        $this->fqnEnd = (string) str($this->argument('name') ?? console()->text(
            label: 'What is the Livewire component name?',
            placeholder: 'ViewBlogPost',
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
        ] = $this->askForLivewireComponentLocation(
            question: 'Where would you like to create the schema?',
        );

        $this->fqn = "{$namespace}\\{$this->fqnEnd}";
        $this->path = (string) str("{$path}\\{$this->fqnEnd}.php")
            ->replace('\\', '/')
            ->replace('//', '/');

        [
            $this->view,
            $this->viewPath,
        ] = $this->askForViewLocation(
            $this->getLivewireComponentViewName($namespace, $this->fqnEnd),
            defaultNamespace: $viewNamespace,
        );
    }

    protected function createLivewireComponent(): void
    {
        if (! $this->option('force') && $this->checkForCollision($this->path)) {
            throw new FailureCommandOutput;
        }

        $this->writeFile($this->path, app(LivewireSchemaComponentClassGenerator::class, [
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

        $this->copyStubToApp('LivewireSchemaView', $this->viewPath);
    }
}
