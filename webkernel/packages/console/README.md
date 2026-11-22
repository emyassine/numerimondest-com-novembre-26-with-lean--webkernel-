# Webkernel Console

The console package parses CLI arguments, loads command classes from the Composer compiled `webkernel_commands.php` manifest, dispatches command methods marked with `#[ConsoleCommand]`, and writes terminal errors.

Command classes are declared through `ServiceProvider::COMMANDS`. The declaration compiler writes the class list during Composer's autoload dump; runtime command dispatch does not scan providers or package directories. Command constructors and middleware are resolved through the Webkernel container.

`Webkernel\Console::run($argv)` is the application CLI entry point. `console()` returns the terminal interface, including interactive prompts and fake answers for non-interactive command flows.

See [Console reference](docs/00-console.en.md) for command registration, argument parsing, dispatch, terminal I/O, and middleware behavior.
