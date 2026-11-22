<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Console;

use Composer\InstalledVersions;
use Webkernel\Container\Container;
use Webkernel\Console\Input\ArgvInput;
use Webkernel\Console\Middleware\ConsoleMiddleware;

/**
 * Runs dumped `#[ConsoleCommand]` methods and maps PHP parameters to argv.
 *
 * `webapp()->console()` is this object. `Webkernel\Console::run()` is the
 * process door: construct, handle(), exit.
 *
 * //> Command classes come from CommandsDiscovery + webkernel_commands.php.
 * //> No hardcoded command list in this class.
 *
 * @phpstan-type CommandDef array{
 *   class: class-string,
 *   method: string,
 *   description: string,
 *   middleware: list<class-string>,
 *   aliases: list<string>,
 *   hidden: bool,
 *   reflection: \ReflectionMethod
 * }
 *
 * //> Command methods take scalars only. Inject collaborators in the constructor.
 */
final class Dispatcher
{
    /** @var array<string, CommandDef>|null */
    private ?array $definitions = null;

    private readonly CommandsDiscovery $discovery;
    private readonly Container $container;

    /**
     * @param CommandsDiscovery $discovery
     */
    public function __construct(CommandsDiscovery $discovery, Container $container)
    {
        $this->discovery = $discovery;
        $this->container = $container;
    }

    /**
     * @param ArgvInput $input
     *
     * @return ExitCode
     */
    public function handle(ArgvInput $input): ExitCode
    {
        $name = $input->command();
        $definitions = $this->definitions();
        $groups = $this->discovery->groups($definitions);
        if ($input->wantsHelp()) {
            if ($name !== null && isset($groups[$name])) {
                $this->printGroupHelp($name, $groups[$name]);
            } else {
                $this->printHelp($name);
            }

            return ExitCode::SUCCESS;
        }

        if ($name === null) {
            $this->printHelp(null);
            if (! $this->canPrompt($input)) {
                return ExitCode::SUCCESS;
            }

            $name = $this->selectCommand($definitions, $groups);
            if ($name === null) {
                return ExitCode::SUCCESS;
            }
            $input = $input->withCommand($name);
        } elseif (! isset($definitions[$name]) && isset($groups[$name])) {
            if (! $this->canPrompt($input)) {
                $this->printGroupHelp($name, $groups[$name]);

                return ExitCode::SUCCESS;
            }

            $name = $this->selectGroupCommand($name, $groups[$name], $definitions);
            if ($name === '@back') {
                $name = $this->selectCommand($definitions, $groups);
            }
            if ($name === null) {
                return ExitCode::SUCCESS;
            }
            $input = $input->withCommand($name);
        }

        $definition = $definitions[$name] ?? null;
        if ($definition === null) {
            console()->error('Unknown command: '.$name);
            $this->printList();

            return ExitCode::INVALID;
        }

        try {
            return CommandSignals::run(function () use ($definition, $input): ExitCode {
                $instance = $this->container->make($definition['class']);
                $next = function () use ($definition, $input, $instance): ExitCode {
                    return $this->invoke($definition, $input, $instance);
                };
                foreach (\array_reverse($definition['middleware']) as $middleware_class) {
                    $middleware = $this->container->make($middleware_class);
                    if (! $middleware instanceof ConsoleMiddleware) {
                        throw new \RuntimeException($middleware_class.' must implement ConsoleMiddleware.');
                    }
                    $inner = $next;
                    $next = static fn (): ExitCode => $middleware->handle($input, $inner);
                }

                return $next();
            });
        } catch (CommandInterrupted $e) {
            console()->warning('Command interrupted (signal '.$e->signal.').');

            return ExitCode::tryFrom(128 + $e->signal) ?? ExitCode::INTERRUPTED;
        } catch (Cancelled $e) {
            console()->error($e->getMessage());

            return ExitCode::ERROR;
        } catch (\InvalidArgumentException $e) {
            console()->error($e->getMessage());

            return ExitCode::INVALID;
        }
    }

    /**
     * @return array<string, CommandDef>
     */
    public function definitions(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        $this->definitions = $this->discovery->definitions(
            $this->discovery->classesFromDump(),
        );

        return $this->definitions;
    }

    private function canPrompt(ArgvInput $input): bool
    {
        return $input->option('interactive') === true
            && ! $this->interactionDisabled($input)
            && (console()->is_interactive() || console()->is_faking());
    }

    private function interactionDisabled(ArgvInput $input): bool
    {
        return $input->option('interaction') === false;
    }

    /** @param array<string, CommandDef> $definitions @param array<string, list<string>> $groups */
    private function selectCommand(array $definitions, array $groups): ?string
    {
        $options = [];
        foreach ($groups[''] ?? [] as $name) {
            if (! isset($groups[$name])) {
                $options[$name] = $name;
            }
        }
        foreach ($groups as $group => $names) {
            if ($group === '') {
                continue;
            }

            $command_count = count($names) + (isset($definitions[$group]) ? 1 : 0);
            $options['@group:'.$group] = $group.' ('.$this->countLabel($command_count, 'command').')';
        }
        $options['@exit'] = 'Exit';

        $info = function (mixed $choice) use ($definitions, $groups): string {
            if (! is_string($choice)) {
                return '';
            }
            if (str_starts_with($choice, '@group:')) {
                $group = substr($choice, 7);
                $descriptions = [];
                if (isset($definitions[$group]) && $definitions[$group]['description'] !== '') {
                    $descriptions[] = $definitions[$group]['description'];
                }
                foreach ($groups[$group] ?? [] as $name) {
                    $description = $definitions[$name]['description'];
                    if ($description !== '') {
                        $descriptions[] = $description;
                    }
                }

                return implode(' · ', array_slice($descriptions, 0, 3));
            }

            return $definitions[$choice]['description'] ?? '';
        };

        while (true) {
            $choice = console()->select(
                'Choose a command',
                $options,
                '@exit',
                info: $info,
            );
            if ($choice === '@exit') {
                return null;
            }
            if (is_string($choice) && str_starts_with($choice, '@group:')) {
                $group = substr($choice, 7);
                $command = $this->selectGroupCommand($group, $groups[$group], $definitions);
                if ($command === '@back') {
                    continue;
                }

                return $command;
            }

            return is_string($choice) && isset($definitions[$choice]) ? $choice : null;
        }
    }

    /** @param list<string> $names @param array<string, CommandDef> $definitions */
    private function selectGroupCommand(string $group, array $names, array $definitions): ?string
    {
        $options = [];
        if (isset($definitions[$group])) {
            $options[$group] = $group;
        }
        foreach ($names as $name) {
            $options[$name] = $name;
        }
        $options['@back'] = 'Back';
        $options['@exit'] = 'Exit';

        $choice = console()->select(
            'Choose a '.$group.' command',
            $options,
            '@exit',
            info: static fn (mixed $name): string => is_string($name)
                ? ($definitions[$name]['description'] ?? '')
                : '',
        );

        if ($choice === '@back') {
            return '@back';
        }
        if ($choice === '@exit') {
            return null;
        }

        return is_string($choice)
            && (($choice === $group && isset($definitions[$group])) || in_array($choice, $names, true))
                ? $choice
                : null;
    }

    private function countLabel(int $count, string $singular): string
    {
        return $count.' '.($count === 1 ? $singular : $singular.'s');
    }

    /**
     * @param CommandDef $definition
     * @param ArgvInput  $input
     * @param object     $instance
     *
     * @return ExitCode
     */
    private function invoke(array $definition, ArgvInput $input, object $instance): ExitCode
    {
        $args = $this->bindParameters($definition['reflection'], $input);
        $result = $definition['reflection']->invokeArgs($instance, $args);

        return self::normalize($result);
    }

    /**
     * @param \ReflectionMethod $method
     * @param ArgvInput         $input
     *
     * @return list<mixed>
     */
    private function bindParameters(\ReflectionMethod $method, ArgvInput $input): array
    {
        $used_options = [];
        $positional = $input->arguments();
        $position = 0;
        $args = [];

        foreach ($method->getParameters() as $param) {
            if ($param->isVariadic()) {
                if (self::scalarType($param) !== 'string') {
                    throw new \RuntimeException('Variadic console arguments must be strings.');
                }
                foreach (\array_slice($positional, $position) as $argument) {
                    $args[] = $argument;
                }
                $position = \count($positional);

                continue;
            }

            $type = self::scalarType($param);
            $kebab = \str_replace('_', '-', $param->getName());
            $is_bool = $type === 'bool';
            $required = ! $is_bool && ! $param->isDefaultValueAvailable();

            if ($required) {
                if (! \array_key_exists($position, $positional)) {
                    if (! $this->canPrompt($input)) {
                        throw new \InvalidArgumentException('Missing argument <'.$param->getName().'>.');
                    }
                    $value = $this->promptValue('Argument <'.$param->getName().'>', $type, $param->allowsNull());
                    $args[] = self::cast($type, $value, $param->allowsNull());
                    continue;
                }
                $args[] = self::cast($type, $positional[$position], $param->allowsNull());
                $position++;
                continue;
            }

            $value = $this->optionValue($param, $input, $kebab, $used_options);
            if ($value !== null) {
                if ($is_bool) {
                    $args[] = $value === true || $value === '1' || $value === 'true';
                    continue;
                }
                $args[] = self::cast($type, $value, $param->allowsNull());
                continue;
            }

            if (! $is_bool && \array_key_exists($position, $positional)) {
                $args[] = self::cast($type, $positional[$position], $param->allowsNull());
                $position++;
                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }
            if ($param->allowsNull()) {
                $args[] = null;
                continue;
            }
            if (! $this->canPrompt($input)) {
                throw new \InvalidArgumentException('Missing option --'.$kebab.'.');
            }
            $value = $this->promptValue('Option --'.$kebab, $type, $param->allowsNull());
            $args[] = self::cast($type, $value, $param->allowsNull());
        }

        if (isset($positional[$position])) {
            throw new \InvalidArgumentException('Unexpected argument ['.$positional[$position].'].');
        }
        foreach ($input->options() as $name => $_) {
            if ($name === 'interaction') {
                continue;
            }
            if (! isset($used_options[$name])) {
                throw new \InvalidArgumentException('Unknown option --'.$name.'.');
            }
        }

        return $args;
    }

    private function promptValue(string $label, string $type, bool $nullable): string
    {
        while (true) {
            $value = console()->text($label, required: ! $nullable);
            try {
                self::cast($type, $value, $nullable);

                return $value;
            } catch (\InvalidArgumentException $exception) {
                console()->error($exception->getMessage());
            }
        }
    }

    /**
     * @param \ReflectionParameter $param
     * @param ArgvInput            $input
     * @param string               $kebab
     * @param array<string, true>  $used_options
     *
     * @return string|bool|null
     */
    private function optionValue(\ReflectionParameter $param, ArgvInput $input, string $kebab, array &$used_options): string|bool|null
    {
        $names = [$kebab];
        if (\str_starts_with($param->getName(), 'with_')) {
            $names[] = \str_replace('_', '-', \substr($param->getName(), 5));
        }

        foreach ($names as $name) {
            if ($input->hasOption($name)) {
                $used_options[$name] = true;
                foreach ($names as $alias) {
                    if ($input->hasOption($alias)) {
                        $used_options[$alias] = true;
                    }
                }

                return $input->option($name);
            }
        }

        foreach ($names as $name) {
            $used_options[$name] = true;
        }

        return null;
    }

    /**
     * @param class-string $class
     *
     * @return object
     */
    /**
     * @param string|null $name
     *
     * @return void
     */
    private function printHelp(?string $name): void
    {
        if ($name === null) {
            $this->printList();

            return;
        }
        $definition = $this->definitions()[$name] ?? null;
        if ($definition === null) {
            console()->error('Unknown command: '.$name);
            $this->printList();

            return;
        }
        $this->printCommand($name, $definition);
    }

    /** @param list<string> $names */
    private function printGroupHelp(string $group, array $names): void
    {
        $definitions = $this->definitions();
        $commands = isset($definitions[$group]) ? [$group, ...$names] : $names;
        $width = max(array_map('strlen', $commands));

        echo "\n  ".Terminal::BOLD.'Usage:'.Terminal::RESET."\n";
        echo '    webkernel '.Terminal::CYAN.$group.Terminal::RESET;
        echo isset($definitions[$group]) ? ' [<command>]' : ' <command>';
        echo " [arguments]\n\n";
        echo '  '.Terminal::BOLD.'Available '.$group.' commands:'.Terminal::RESET."\n\n";
        foreach ($commands as $name) {
            $definition = $definitions[$name];
            $pad = str_repeat(' ', $width - strlen($name) + 3);
            $description = $definition['description'] !== ''
                ? $definition['description']
                : $this->signatureHint($definition['reflection']);
            echo '  '.Terminal::CYAN.$name.Terminal::RESET.$pad.Terminal::muted($description)."\n";
        }
        echo "\n";
    }

    /**
     * @return void
     */
    private function printList(): void
    {
        $definitions = $this->definitions();
        $version = $this->frameworkVersion();

        echo "\n";
        echo '  '.Terminal::BOLD.'Webkernel'.Terminal::RESET;
        if ($version !== '') {
            echo ' '.Terminal::muted($version);
        }
        echo "\n\n";
        echo '  '.Terminal::BOLD.'Usage:'.Terminal::RESET."\n";
        echo '    webkernel '.Terminal::muted('<command> [options] [arguments]')."\n\n";
        echo '  '.Terminal::BOLD.'Available commands:'.Terminal::RESET."\n";

        $width = 4;
        foreach (array_keys($definitions) as $name) {
            $width = max($width, strlen($name));
        }

        $groups = $this->discovery->groups($definitions);
        foreach ($groups as $group => $names) {
            if ($group !== '') {
                echo "\n  ".Terminal::YELLOW.$group.Terminal::RESET."\n";
            } else {
                echo "\n";
            }
            if ($group !== '' && isset($definitions[$group])) {
                $this->printListCommand($group, $definitions[$group], $width, '    ');
            }
            foreach ($names as $name) {
                if ($group === '' && isset($groups[$name])) {
                    continue;
                }
                $indent = $group !== '' ? '    ' : '  ';
                $this->printListCommand($name, $definitions[$name], $width, $indent);
            }
        }
        echo "\n";
    }

    /** @param CommandDef $definition */
    private function printListCommand(string $name, array $definition, int $width, string $indent): void
    {
        $pad = str_repeat(' ', $width - strlen($name) + 3);
        $description = $definition['description'] !== ''
            ? $definition['description']
            : $this->signatureHint($definition['reflection']);
        echo $indent.Terminal::CYAN.$name.Terminal::RESET.$pad.Terminal::muted($description)."\n";
    }

    /**
     * @param string     $name
     * @param CommandDef $definition
     *
     * @return void
     */
    private function printCommand(string $name, array $definition): void
    {
        $primary = $name;
        foreach ($this->definitions() as $candidate => $candidate_definition) {
            if (
                $candidate_definition['class'] === $definition['class']
                && $candidate_definition['method'] === $definition['method']
                && ! \in_array($candidate, $definition['aliases'], true)
            ) {
                $primary = $candidate;
                break;
            }
        }
        $hint = $this->signatureHint($definition['reflection']);
        echo "\n  ".Terminal::BOLD.'Usage:'.Terminal::RESET."\n";
        echo '    webkernel '.Terminal::CYAN.$primary.Terminal::RESET;
        if ($hint !== '') {
            echo ' '.Terminal::muted($hint);
        }
        echo "\n";
        if ($definition['description'] !== '') {
            echo "\n  ".$definition['description']."\n";
        }
        if ($definition['aliases'] !== []) {
            echo "\n  ".Terminal::BOLD.'Aliases:'.Terminal::RESET.' '
                .\implode(', ', $definition['aliases'])."\n";
        }
        echo "\n";
    }

    /**
     * @param \ReflectionMethod $method
     *
     * @return string
     */
    private function signatureHint(\ReflectionMethod $method): string
    {
        $parts = [];
        foreach ($method->getParameters() as $param) {
            if ($param->isVariadic()) {
                $parts[] = '['.$param->getName().'...]';

                continue;
            }
            $kebab = \str_replace('_', '-', $param->getName());
            $type = self::scalarType($param);
            if ($type === 'bool') {
                $parts[] = '[--'.$kebab.']';
                if (\str_starts_with($param->getName(), 'with_')) {
                    $parts[] = '[--no-'.\str_replace('_', '-', \substr($param->getName(), 5)).']';
                }
                continue;
            }
            if ($param->isDefaultValueAvailable()) {
                $default = $param->getDefaultValue();
                $shown = \is_scalar($default) ? (string) $default : '';
                $parts[] = '['.$param->getName().($shown !== '' ? '='.$shown : '').']';
                continue;
            }
            $parts[] = '<'.$param->getName().'>';
        }

        return \implode(' ', $parts);
    }

     /**
      * @return string
      */
     private function frameworkVersion(): string
     {
     	return InstalledVersions::isInstalled('webkernel/codebase')
            ? (InstalledVersions::getPrettyVersion('webkernel/codebase') ?? '')
            : (InstalledVersions::getPrettyVersion('webkernel/console') ?? '');
     }

    /**
     * @param \ReflectionParameter $param
     *
     * @return string
     */
    private static function scalarType(\ReflectionParameter $param): string
    {
        $type = $param->getType();
        if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
            throw new \RuntimeException('Command parameter $'.$param->getName().' must be a scalar; inject '.$type->getName().' in the constructor.');
        }
        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        return 'string';
    }

    /**
     * @param string $type
     * @param mixed  $value
     * @param bool   $nullable
     *
     * @return mixed
     */
    private static function cast(string $type, mixed $value, bool $nullable): mixed
    {
        if ($value === null || $value === true || $value === false) {
            if ($nullable && $value === null) {
                return null;
            }
            if ($type === 'bool') {
                return $value === true;
            }
            throw new \InvalidArgumentException('Expected '.$type.' value.');
        }
        $raw = (string) $value;

        return match ($type) {
            'int' => (int) $raw,
            'float' => (float) $raw,
            'bool' => $raw === '1' || $raw === 'true',
            default => $raw,
        };
    }

    /**
     * @param mixed $result
     *
     * @return ExitCode
     */
    private static function normalize(mixed $result): ExitCode
    {
        if ($result instanceof ExitCode) {
            return $result;
        }
        if (\is_int($result)) {
            return ExitCode::tryFrom($result) ?? ExitCode::ERROR;
        }
        if ($result === null) {
            return ExitCode::SUCCESS;
        }

        throw new \RuntimeException('Console command must return ExitCode, int, or void.');
    }
}
