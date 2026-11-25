<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Commands;

use Webkernel\Console\Attribute\ConsoleCommand;
use Webkernel\Commands\ServerCommand\Engine;
use Webkernel\Commands\ServerCommand\Performance;
use Webkernel\Console\ExitCode;

final readonly class ServerCommand
{
    /**
     * @param Engine $engine
     */
    public function __construct(
        private Engine $engine,
        private AssetsCommand $assets,
    ) {
    }

    /**
     * @param string      $host
     * @param int         $port
     * @param bool        $profile_lifecycle
     * @param bool|null   $with_jit
     *
     * @return ExitCode
     */
    #[ConsoleCommand(
        name: 'server',
        description: 'Sync frontend assets, then serve locally (--host= --port= --mount=/prefix:/directory --profile-lifecycle --with-jit)',
    )]
    public function __invoke(
        string $host = '127.0.0.1',
        int $port = 8000,
        bool $profile_lifecycle = false,
        ?bool $with_jit = null,
        array $mount = [],
    ): ExitCode {
        console()->info('Synchronizing frontend assets before starting the server...');
        $asset_result = $this->assets->sync();
        if ($asset_result !== 0) {
            return ExitCode::tryFrom($asset_result) ?? ExitCode::ERROR;
        }

        $jit = $with_jit;
        if ($jit === null) {
            $jit = Performance::wants_jit();
        }

        return $this->engine->serve($host, $port, $profile_lifecycle, $jit, $mount);
    }
}
