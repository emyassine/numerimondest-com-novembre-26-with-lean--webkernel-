<?php

namespace Webkernel\Platform\Forms\Commands;


use function console;
use Webkernel\Platform\Forms\Commands\FileGenerators\FormSchemaClassGenerator;
use Webkernel\Platform\Support\Commands\Concerns\CanAskForComponentLocation;
use Webkernel\Platform\Support\Commands\Concerns\CanManipulateFiles;
use Webkernel\Platform\Support\Commands\Exceptions\FailureCommandOutput;
use \Webkernel\Console\Command;
use Webkernel\ORM\Model;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Webkernel\Platform\Support\discover_app_classes;
#[AsCommand(name: 'make:platform-form', aliases: [
    'platform:form',
])]
class MakeFormCommand extends Command
{
    use CanAskForComponentLocation;
    use CanManipulateFiles;

    protected $description = 'Create a new Platform form schema class';

    protected $name = 'make:platform-form';

    /**
     * @var array<string>
     */
    protected $aliases = [
        'platform:form',
    ];

    /**
     * @var class-string
     */
    protected string $fqn;

    protected string $fqnEnd;

    protected string $path;

    /**
     * @var ?class-string<Model>
     */
    protected ?string $modelFqn = null;

    protected ?string $modelFqnEnd = null;

    /**
     * @return array<InputArgument>
     */
    protected function getArguments(): array
    {
        return [
            new InputArgument(
                name: 'name',
                mode: InputArgument::OPTIONAL,
                description: 'The name of the form schema class to generate, optionally prefixed with directories',
            ),
            new InputArgument(
                name: 'model',
                mode: InputArgument::OPTIONAL,
                description: 'The name of the model to generate the form for, optionally prefixed with directories',
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
                name: 'model-namespace',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The namespace of the model class, [' . app()->getNamespace() . 'Models] by default',
            ),
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
            $this->configureModel();

            $this->configureLocation();

            $this->createSchema();
        } catch (FailureCommandOutput) {
            return static::FAILURE;
        }

        $this->components->info("Form schema [{$this->fqn}] created successfully.");

        return static::SUCCESS;
    }

    protected function configureFqnEnd(): void
    {
        $this->fqnEnd = (string) str($this->argument('name') ?? console()->text(
            label: 'What is the form schema name?',
            placeholder: 'BlogPostForm',
            required: true,
        ))
            ->trim('/')
            ->trim('\\')
            ->trim(' ')
            ->studly()
            ->replace('/', '\\');
    }

    protected function configureModel(): void
    {
        if ($this->argument('model')) {
            $this->modelFqnEnd = (string) str($this->argument('model'))
                ->trim('/')
                ->trim('\\')
                ->trim(' ')
                ->studly()
                ->replace('/', '\\');

            $modelNamespace = $this->option('model-namespace') ?? app()->getNamespace() . 'Models';

            $this->modelFqn = "{$modelNamespace}\\{$this->modelFqnEnd}";

            return;
        }

        if (! console()->confirm(
            label: 'Would you like to create a form for a model?',
            default: false,
        )) {
            return;
        }

        $modelFqns = discover_app_classes(parentClass: Model::class);

        $this->modelFqn = console()->suggest(
            label: 'What is the model?',
            options: function (string $search) use ($modelFqns): array {
                $search = str($search)->trim()->replace(['\\', '/'], '');

                if (blank($search)) {
                    return $modelFqns;
                }

                return array_filter(
                    $modelFqns,
                    fn (string $class): bool => str($class)->replace(['\\', '/'], '')->contains($search, ignoreCase: true),
                );
            },
            placeholder: app()->getNamespace() . 'Models\\BlogPost',
            required: true,
        );

        $this->modelFqnEnd = class_basename($this->modelFqn);
    }

    protected function configureLocation(): void
    {
        [
            $namespace,
            $path,
        ] = $this->askForComponentLocation(
            path: 'Schemas',
            question: 'Where would you like to create the form schema?',
        );

        $this->fqn = "{$namespace}\\{$this->fqnEnd}";
        $this->path = (string) str("{$path}\\{$this->fqnEnd}.php")
            ->replace('\\', '/')
            ->replace('//', '/');
    }

    protected function createSchema(): void
    {
        if (! $this->option('force') && $this->checkForCollision($this->path)) {
            throw new FailureCommandOutput;
        }

        $this->writeFile($this->path, app(FormSchemaClassGenerator::class, [
            'fqn' => $this->fqn,
            'modelFqn' => $this->modelFqn,
        ]));
    }
}
