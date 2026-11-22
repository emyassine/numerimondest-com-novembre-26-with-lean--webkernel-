<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Commands\Providers;

use Webkernel\Commands\BinCommand;
use Webkernel\Commands\MakeMigrationCommand;
use Webkernel\Commands\RouteListCommand;
use Webkernel\Commands\AssetsCommand;
use Webkernel\Commands\CompletionCommand;
use Webkernel\Commands\DeployCommand;
use Webkernel\Commands\ServerCommand;
use Webkernel\ServiceProvider;

/** Declares commands supplied by the core commands package. */
final class CommandsProvider extends ServiceProvider
{
    public const array COMMANDS = [
        AssetsCommand::class,
        DeployCommand::class,
        CompletionCommand::class,
        ServerCommand::class,
        BinCommand::class,
        MakeMigrationCommand::class,
        RouteListCommand::class,
    ];
}
