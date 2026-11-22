<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Console;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webkernel\Container\Container;
use Webkernel\Console\Application;
use Webkernel\Console\Parser;
use Webkernel\Console\Terminal;

/** Symfony command lifecycle with the command APIs consumed by Filament and Livewire. */
class Command extends SymfonyCommand
{
    protected $name;
    protected $description = '';
    protected $signature;
    protected $input;
    protected $output;
    protected Terminal $components;
    protected Container $laravel;

    public function __construct(?string $name = null)
    {
        $this->laravel = Container::getInstance();
        $this->components = console();
        if ($this->signature) {
            [$signature_name, $arguments, $options] = Parser::parse($this->signature);
            $name ??= $signature_name;
        }
        parent::__construct($name ?? $this->name);
        if ($this->description !== '') { $this->setDescription($this->description); }
        if (isset($arguments, $options)) { $this->getDefinition()->addArguments($arguments); $this->getDefinition()->addOptions($options); }
        foreach ($this->getArguments() as $argument) { $this->addArgument(...$argument); }
        foreach ($this->getOptions() as $option) { $this->addOption(...$option); }
    }

    protected function getArguments() { return []; }
    protected function getOptions() { return []; }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        return (int) ($this->laravel->call([$this, 'handle']) ?? 0);
    }
    public function argument($key = null): mixed { return $key === null ? $this->input->getArguments() : $this->input->getArgument($key); }
    public function arguments(): array { return $this->input->getArguments(); }
    public function option($key = null): mixed { return $key === null ? $this->input->getOptions() : $this->input->getOption($key); }
    public function options(): array { return $this->input->getOptions(); }
    public function hasArgument($name): bool { return $this->getDefinition()->hasArgument($name); }
    public function hasOption($name): bool { return $this->getDefinition()->hasOption($name); }
    public function info($message, $verbosity = null): void { $this->output->writeln('<info>'.$message.'</info>'); }
    public function warn($message, $verbosity = null): void { $this->output->writeln('<comment>'.$message.'</comment>'); }
    public function error($message, $verbosity = null): void { $this->output->writeln('<error>'.$message.'</error>'); }
    public function line($message, $style = null, $verbosity = null): void { $this->output->writeln($message); }
    public function comment($message, $verbosity = null): void { $this->warn($message); }
    public function newLine($count = 1): void { $this->output->write(str_repeat(PHP_EOL, $count)); }
    public function call($command, array $arguments = [], $outputBuffer = null): int
    {
        $application = $this->getApplication();
        return ($application instanceof Application ? $application : new Application())->call($command, $arguments, $outputBuffer ?? $this->output);
    }
    public function callSilently($command, array $arguments = []): int { return $this->call($command, $arguments, new \Symfony\Component\Console\Output\NullOutput()); }
    public function ask($question, $default = null): string { return console()->text($question, default: (string) $default); }
    public function confirm($question, $default = false): bool { return console()->confirm($question, $default); }
    public function secret($question, $fallback = true): string { return console()->password($question); }
    public function getLaravel(): Container { return $this->laravel; }
    public function setLaravel(Container $container): void { $this->laravel = $container; }
    public function getOutput(): OutputInterface { return $this->output; }
    public function getInput(): InputInterface { return $this->input; }
}
