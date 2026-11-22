<?php

namespace Webkernel\Platform\Support\Commands\Concerns;


use function console;
use Webkernel\Platform\Commands\FileGenerators\PanelProviderClassGenerator;
use Webkernel\Platform\Facades\Platform;
use Webkernel\Platform\Support\Commands\Exceptions\FailureCommandOutput;
use \Webkernel\Container\Container as App;
use \Webkernel\ServiceProvider;
use Webkernel\Utilities\Str;

trait CanGeneratePanels
{
    use CanManipulateFiles;

    public function generatePanel(?string $id = null, string $defaultId = '', string $placeholderId = '', bool $isForced = false): void
    {
        $id = Str::lcfirst($id ?? console()->text(
            label: 'What is the panel\'s ID?',
            placeholder: $placeholderId,
            default: $defaultId,
            required: true,
            validate: fn (string $value) => match (true) {
                preg_match('/^[a-zA-Z].*/', $value) !== false => null,
                default => 'The ID must start with a letter, and not a number or special character.',
            },
            hint: 'It must be unique to any others you have, and is used to reference the panel in your code.',
        ));

        $basename = (string) str($id)
            ->studly()
            ->append('PanelProvider');

        $path = app_path(
            (string) str($basename)
                ->prepend('Providers/Platform/')
                ->replace('\\', '/')
                ->append('.php'),
        );

        if (! $isForced && $this->checkForCollision([$path])) {
            throw new FailureCommandOutput;
        }

        $fqn = app()->getNamespace() . "Providers\\Webkernel\Platform\\{$basename}";

        if (empty(Platform::getPanels())) {
            $this->writeFile($path, app(PanelProviderClassGenerator::class, [
                'fqn' => $fqn,
                'id' => $id,
                'isDefault' => true,
            ]));
        } else {
            $this->writeFile($path, app(PanelProviderClassGenerator::class, [
                'fqn' => $fqn,
                'id' => $id,
            ]));
        }

        $hasBootstrapProvidersFile = file_exists($bootstrapProvidersPath = App::getBootstrapProvidersPath());

        if ($hasBootstrapProvidersFile) {
            ServiceProvider::addProviderToBootstrapFile(
                $fqn,
                $bootstrapProvidersPath,
            );
        } else {
            $appConfig = file_get_contents(config_path('app.php'));

            if (! Str::contains($appConfig, "{$fqn}::class")) {
                file_put_contents(config_path('app.php'), str_replace(
                    app()->getNamespace() . 'Providers\\RouteServiceProvider::class,',
                    "{$fqn}::class," . PHP_EOL . '        ' . app()->getNamespace() . 'Providers\\RouteServiceProvider::class,',
                    $appConfig,
                ));
            }
        }

        $this->components->info("Platform panel [{$path}] created successfully.");

        if ($hasBootstrapProvidersFile) {
            $this->components->warn("We've attempted to register the {$basename} in your [bootstrap/providers.php] file. If you get an error while trying to access your panel then this process has probably failed. You can manually register the service provider by adding it to the array.");
        } else {
            $this->components->warn("We've attempted to register the {$basename} in your [config/app.php] file as a service provider.  If you get an error while trying to access your panel then this process has probably failed. You can manually register the service provider by adding it to the [providers] array.");
        }
    }
}
