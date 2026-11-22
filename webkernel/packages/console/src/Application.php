<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Console;

use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Webkernel\Container\Container;

class Application extends SymfonyApplication
{
    private static array $starting = [];
    private ?BufferedOutput $last_output = null;

    public function __construct()
    {
        parent::__construct('Webkernel', '0.12.0');
        $this->setAutoExit(false);
        foreach (self::$starting as $callback) { $callback($this); }
    }

    public static function starting(callable $callback): void { self::$starting[] = $callback; }
    public function resolveCommands(array $commands): void { foreach ($commands as $command) { $this->add(Container::getInstance()->make($command)); } }
    public function call(string $command, array $parameters = [], ?OutputInterface $output = null): int
    {
        $input = new ArrayInput(['command' => $command] + $parameters);
        $input->setInteractive(false);
        $output ??= $this->last_output = new BufferedOutput();
        return $this->run($input, $output);
    }
    public function output(): string { return $this->last_output?->fetch() ?? ''; }
}
