<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Console;

use Webkernel\Console\Attribute\ConsoleCommand;

/**
 * Reads the compiled command class list and builds `#[ConsoleCommand]` definitions.
 *
 * //> Dump-time writes the class list. Runtime reflects attributes from that dump.
 * //> Discovery is not DumpAutoloadCommand's job.
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
 */
final class CommandsDiscovery
{
    /**
     * Load dumped command classes from `{root}/runtime/cache/declarations/webkernel_commands.php`.
     *
     * @param string|null $file
     *
     * @return list<class-string>
     */
    public function classesFromDump(?string $file = null): array
    {
        $file ??= \base_path('runtime/cache/declarations/webkernel_commands.php');
        if ($file === '' || ! \is_file($file)) {
            return [];
        }
        $loaded = require $file;
        if (! \is_array($loaded)) {
            return [];
        }
        $out = [];
        foreach ($loaded as $class) {
            if (! \is_string($class) || $class === '') {
                continue;
            }
            if (! \class_exists($class)) {
                continue;
            }
            $out[] = $class;
        }

        return $out;
    }

    /**
     * Reflect `#[ConsoleCommand]` methods on the given classes.
     *
     * @param list<class-string> $classes
     *
     * @return array<string, CommandDef>
     *
     * @throws \RuntimeException
     */
    public function definitions(array $classes): array
    {
        $out = [];
        foreach ($classes as $class) {
            if (! \class_exists($class)) {
                continue;
            }
            $ref = new \ReflectionClass($class);
            foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $attrs = $method->getAttributes(ConsoleCommand::class);
                if ($attrs === []) {
                    continue;
                }
                /** @var ConsoleCommand $attribute */
                $attribute = $attrs[0]->newInstance();
                $command_name = self::commandName($ref, $method, $attribute);
                $definition = [
                    'class' => $class,
                    'method' => $method->getName(),
                    'description' => $attribute->description,
                    'middleware' => $attribute->middleware,
                    'aliases' => $attribute->aliases,
                    'hidden' => $attribute->hidden,
                    'reflection' => $method,
                ];
                foreach ([$command_name, ...$attribute->aliases] as $name) {
                    if ($name === '') {
                        continue;
                    }
                    if (isset($out[$name])) {
                        throw new \RuntimeException('Duplicate console command ['.$name.'].');
                    }
                    $out[$name] = $definition;
                }
            }
        }
        \ksort($out);

        return $out;
    }

    /**
     * Group visible command names by the segment before `:` (Symfony-style).
     *
     * @param array<string, CommandDef> $definitions
     *
     * @return array<string, list<string>>
     */
    public function groups(array $definitions): array
    {
        $groups = [];
        foreach ($definitions as $name => $definition) {
            if ($definition['hidden'] || \in_array($name, $definition['aliases'], true)) {
                continue;
            }
            $colon = \strpos($name, ':');
            $group = $colon === false ? '' : \substr($name, 0, $colon);
            $groups[$group][] = $name;
        }
        \ksort($groups);
        foreach ($groups as &$names) {
            \sort($names, \SORT_STRING);
        }
        unset($names);

        return $groups;
    }

    /**
     * Non-hidden primary names and aliases for shell completion.
     *
     * @param array<string, CommandDef> $definitions
     *
     * @return list<string>
     */
    public function completionNames(array $definitions): array
    {
        $names = [];
        foreach ($definitions as $name => $definition) {
            if ($definition['hidden']) {
                continue;
            }
            $names[] = $name;
        }
        $names = \array_values(\array_unique($names));
        \sort($names, \SORT_STRING);

        return $names;
    }

    /**
     * @param \ReflectionClass<covariant object> $class
     * @param \ReflectionMethod                  $method
     * @param ConsoleCommand                     $attribute
     *
     * @return string
     */
    private static function commandName(\ReflectionClass $class, \ReflectionMethod $method, ConsoleCommand $attribute): string
    {
        if ($attribute->name !== null && $attribute->name !== '') {
            return $attribute->name;
        }
        $short = $class->getShortName();
        if (\str_ends_with($short, 'Command')) {
            $short = \substr($short, 0, -7);
        }
        $class_part = self::kebab($short);
        if ($method->getName() === '__invoke') {
            return $class_part;
        }

        return $class_part.':'.self::kebab($method->getName());
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private static function kebab(string $name): string
    {
        $kebab = \preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $name);
        $kebab = \str_replace('_', '-', \is_string($kebab) ? $kebab : $name);

        return \strtolower($kebab);
    }
}
