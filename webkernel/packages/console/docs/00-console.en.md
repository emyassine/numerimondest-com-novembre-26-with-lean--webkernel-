# Webkernel Console

`webkernel/console` provides the CLI input parser, command dispatcher, terminal output, prompts, and command middleware. The application executable calls `Webkernel\Console::run($argv)`. That method boots the application configuration and container, dispatches the command, and exits with its result.

## Command registration

A command is a public method marked with `#[ConsoleCommand]`. Its class is declared by a service provider through `ServiceProvider::COMMANDS`. Composer's autoload dump compiles these class declarations into `webkernel_commands.php`; runtime dispatch reads this manifest instead of scanning providers or package directories.

```php
use Webkernel\Console\Attribute\ConsoleCommand;
use Webkernel\ServiceProvider;

final class GreetCommand
{
    #[ConsoleCommand(name: 'greet', description: 'Print a greeting')]
    public function __invoke(string $name = 'World'): int
    {
        echo 'Hello '.$name.PHP_EOL;

        return 0;
    }
}

final class ApplicationProvider extends ServiceProvider
{
    public const array COMMANDS = [GreetCommand::class];
}
```

An explicit `name` sets the CLI name. Without it, the class name becomes kebab-case and a non-`__invoke` method adds a `:`-separated segment. `description`, `aliases`, `middleware`, and `hidden` configure help output and dispatch. Hidden commands are omitted from visible help and shell completion.

## Input and parameter binding

The first non-option token selects the command. Remaining tokens are positional arguments unless they are options. The parser accepts `--option=value`, boolean `--option`, and negative boolean `--no-option`. `--` ends option parsing and passes every following token as a positional argument. `--help`, `-h`, and `help <command>` request help.

The dispatcher maps command method parameters in declaration order:

- Required scalar parameters consume positional arguments.
- Optional scalar parameters accept their default, a same-named kebab-case option, or a positional value.
- Boolean parameters are flags. A parameter named `with_feature` accepts `--with-feature` and `--feature` forms.
- A variadic string parameter receives the remaining positional arguments.
- Non-scalar dependencies belong in the command constructor and are resolved through the container.

Unknown options, unexpected arguments, and missing required values are reported as invalid input. Methods return `ExitCode`, an integer, or `void`; integer values outside the defined enum map to `ERROR`.

## Dispatch and interaction

`./platform` with no command prints the command list and exits. Add `--interactive` to open a command selector in a terminal. A group such as `assets` prints its help by default; add `--interactive` to select a subcommand. When a command shares its name with a group, the exact command runs; use `--help` to list the group's subcommands. Missing required arguments and options may be prompted for in interactive mode; `--no-interaction` disables these prompts. Non-interactive invocations report missing inputs and return an error.

The `middleware` attribute field lists middleware class names. Each middleware implements `ConsoleMiddleware::handle(ArgvInput $input, callable $next): ExitCode`. Middleware wraps command invocation in the listed order. Command classes and middleware are created by the container.

## Terminal API

The Composer-loaded `console()` function returns a `Webkernel\Console\Terminal`. It provides status output (`info`, `warning`, `success`, `error`), tables, and prompt methods:

```php
$name = console()->text('Name', required: true);
$confirmed = console()->confirm('Continue?', default: false);
$choice = console()->select('Environment', ['dev' => 'Development', 'prod' => 'Production']);
```

Other prompt methods include `number`, `textarea`, `password`/`secret`, `multiselect`, `suggest`, `search`, and `pause`. Prompts validate values and repeat interactive input after validation errors. Without a TTY, text prompts use their defaults or read a line; confirmation and selection use their configured defaults.

`console()->fake([...])` queues answers for prompt-driven flows. Fake mode avoids TTY input and is intended for deterministic tests or scripted command flows. `is_interactive()` and `is_faking()` report the current terminal mode.

## Exit codes

`ExitCode::SUCCESS` is `0`, `ERROR` is `1`, `INVALID` is `2`, and `INTERRUPTED` is `130`; signal-specific statuses use the conventional `128 + signal` value when represented by `ExitCode`. The dispatcher handles `SIGINT` and `SIGTERM` during command setup and execution, lets `finally` cleanup run, and returns the interrupt status. Commands can catch `CommandInterrupted` when they need signal-specific cleanup. The dispatcher returns the selected method's normalized result to `Webkernel\Console::run()`, which uses it as the process exit status.
