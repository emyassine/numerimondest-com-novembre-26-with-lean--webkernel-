# Built-in commands

Run commands from the application root with `./platform`. Running `./platform` without a command prints the available commands.

## How command execution works

`./platform` passes its argument vector to `Webkernel\Console::run()`. The console reads command classes from the Composer-generated `webkernel_commands.php` manifest, resolves the command class through the container, maps CLI input to the selected method, and returns the method's exit code to the process. It does not scan package directories at runtime.

Commands are public methods marked with `#[ConsoleCommand]` on classes listed in a provider's `ServiceProvider::COMMANDS`. The attribute sets the command name, description, aliases, middleware, and whether the command is hidden. Names containing `:` appear under a command group, such as `assets:build`.

Method parameters define the CLI interface. Required scalar parameters are positional arguments; optional scalar parameters can use their default or a matching `--kebab-case=value` option. Boolean parameters map to flags, and a final variadic `string ...$arguments` receives remaining positional arguments. A `--` token sends all following tokens through as positional arguments. The console supports `--help`, `-h`, and `help <command>`.

When a command or command group is entered without its subcommand in an interactive terminal, the console offers a selection. Missing required values are prompted for interactively. In non-interactive use, missing and invalid input returns an error instead. `--no-interaction` disables prompts. Commands return `Webkernel\Console\ExitCode`, an integer exit code, or `void`.

Running `./platform` with no command prints help and exits without opening a selector. Use `./platform --interactive` to opt into the command selector. `./platform route:list` lists application routes and Composer-compiled panel routes.

The Console package owns parsing, dispatch, parameter binding, terminal output, prompts, and command middleware. Its [Console reference](../../console/docs/00-console.en.md) describes the API and runtime behavior.

| Command | Description |
|---|---|
| [`bin`](01-bin.en.md) | Run a Composer-installed executable. |
| [`completion`](02-completion.en.md) | Print Bash or Zsh shell completion. |
| [`server`](03-server.en.md) | Start the local HTTP server. |
| [`assets:build`](04-assets-build.en.md) | Build frontend assets. |
| [`assets:install`](05-assets-install.en.md) | Install locked frontend dependencies. |
| [`assets:sync`](06-assets-sync.en.md) | Install dependencies, build assets, and refresh the asset manifest. |
| [`deploy`](07-deploy.en.md) | Alias for the non-pruning `deploy:test` preparation. |
| [`deploy:test`](07-deploy.en.md) | Prepare runtime artifacts without changing Node dependencies. |
| [`deploy:prod`](07-deploy.en.md) | Build frontend assets, retain production Node dependencies, and activate runtime artifacts. |
| [`route:list`](08-route-list.en.md) | List application and compiled panel routes. |
