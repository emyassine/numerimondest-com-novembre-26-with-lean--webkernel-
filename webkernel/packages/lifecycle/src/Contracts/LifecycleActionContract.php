<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Contracts;

use Composer\Script\Event;

/**
 * One action = one code-generation responsibility.
 *
 * Actions are created without constructor arguments and may run on every
 * `composer dump-autoload`, so generated output should be idempotent.
 */
interface LifecycleActionContract
{
    /**
     * Unique key identifying what this action generates.
     * Used for logging and future diffing.
     * Example: "tailwind-config", "env-stub"
     * @return string Stable action identifier.
     */
    public function key(): string;

    /**
     * Human-readable label shown in Composer output.
     * Example: "Generate Tailwind config stub"
     * @return string Display name.
     */
    public function name(): string;

    /**
     * Executes the action with the current Composer script event.
     * @param $event Event Composer event that triggered the action.
     */
    public function handle(Event $event): void;
}
