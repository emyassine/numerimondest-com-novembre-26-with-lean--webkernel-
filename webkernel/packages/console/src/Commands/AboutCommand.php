<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Console\Commands;

use Webkernel\Console\Command;

class AboutCommand extends Command
{
    protected $signature = 'about';
    protected $description = 'Display application and package information';
    private static array $sections = [];
    public static function add(string $section, array|callable $data, int $verbosity = 0): void { self::$sections[$section] = $data; }
    public function handle(): int
    {
        foreach (self::$sections as $section => $data) {
            $this->info($section);
            foreach (is_callable($data) ? $data() : $data as $name => $value) { $this->line($name.': '.(string) $value); }
        }
        return self::SUCCESS;
    }
}
