<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Console\Input;

/**
 * Parsed `$argv`. Script name is discarded. `--help` / `-h` / `help`
 * set {@see wantsHelp()}; `help server` leaves command `server`.
 *
 * `--foo` → true, `--no-foo` → false, `--foo=bar` → `"bar"`.
 */
final readonly class ArgvInput
{
    /** @var list<string> */
    private array $arguments;

    /** @var array<string, string|bool|list<string|bool>> */
    private array $options;

    private ?string $command;

    private bool $wantsHelp;

    /**
     * @param list<string>|null $argv
     * @param array{command: string|null, arguments: list<string>, options: array<string, string|bool|list<string|bool>>, wants_help: bool}|null $state
     */
    public function __construct(?array $argv = null, ?array $state = null)
    {
        if ($state !== null) {
            $this->command = $state['command'];
            $this->arguments = $state['arguments'];
            $this->options = $state['options'];
            $this->wantsHelp = $state['wants_help'];

            return;
        }

        $argv ??= $_SERVER['argv'] ?? [];
        if (! \is_array($argv)) {
            $argv = [];
        }

        $command = null;
        $arguments = [];
        $options = [];
        $wantsHelp = false;
        $passthrough = false;

        foreach (\array_slice(\array_values($argv), 1) as $token) {
            if (! \is_string($token) || $token === '') {
                continue;
            }
            if ($command !== null && $token === '--') {
                $passthrough = true;
                continue;
            }
            if ($passthrough) {
                $arguments[] = $token;
                continue;
            }
            if ($token === '--help' || $token === '-h') {
                $wantsHelp = true;
                continue;
            }
            if (\str_starts_with($token, '--no-') && \strlen($token) > 5 && ! \str_contains($token, '=')) {
                $options[\substr($token, 5)] = false;
                continue;
            }
            if (\str_starts_with($token, '--') && \strlen($token) > 2) {
                $body = \substr($token, 2);
                $eq = \strpos($body, '=');
                if ($eq === false) {
                    $value = true;
                } else {
                    $name = \substr($body, 0, $eq);
                    $value = \substr($body, $eq + 1);
                }
                if ($eq === false) $name = $body;
                if (isset($options[$name])) {
                    $options[$name] = [...(\is_array($options[$name]) ? $options[$name] : [$options[$name]]), $value];
                } else {
                    $options[$name] = $value;
                }
                continue;
            }
            if ($command === null) {
                $command = $token;
                continue;
            }
            $arguments[] = $token;
        }

        if ($command === 'help') {
            $wantsHelp = true;
            $command = $arguments[0] ?? null;
            $arguments = \array_slice($arguments, 1);
        }

        $this->command = $command;
        $this->arguments = $arguments;
        $this->options = $options;
        $this->wantsHelp = $wantsHelp;
    }

    /**
     * @return string|null
     */
    public function command(): ?string
    {
        return $this->command;
    }

    public function withCommand(string $command): self
    {
        return new self(state: [
            'command' => $command,
            'arguments' => $this->arguments,
            'options' => $this->options,
            'wants_help' => false,
        ]);
    }

    /**
     * @return list<string>
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return array<string, string|bool|list<string|bool>>
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * @param string $name
     *
     * @return string|bool|list<string|bool>|null
     */
    public function option(string $name): string|bool|array|null
    {
        return $this->options[$name] ?? null;
    }

    /** @return list<string|bool> */
    public function optionValues(string $name): array
    {
        $value = $this->options[$name] ?? null;
        return $value === null ? [] : (\is_array($value) ? $value : [$value]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return \array_key_exists($name, $this->options);
    }

    /**
     * @return bool
     */
    public function wantsHelp(): bool
    {
        return $this->wantsHelp;
    }
}
