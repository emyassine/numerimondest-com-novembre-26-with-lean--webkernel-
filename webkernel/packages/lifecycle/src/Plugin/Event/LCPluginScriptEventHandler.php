<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
namespace Webkernel\Lifecycle\Plugin\Event;

use Composer\Script\Event;
use Throwable;
use Webkernel\Lifecycle\StandardComposerIO;
use Webkernel\Lifecycle\Declaration\LCDeclarationReader;
use Webkernel\Lifecycle\Event\LCEventDispatcher;

/** Handles Composer script events subscribed to by the Lifecycle plugin. */
final class LCPluginScriptEventHandler
{
    /**
     * Dispatches package listeners declared for the Composer event.
     * @param $event Event Composer script event to dispatch.
     * @return void
     * @throws \Throwable If declaration loading or a listener fails.
     */
    public function handle(Event $event): void
    {
        $previous_io = StandardComposerIO::bind($event->getIO());

        try {
            $event_started_at = hrtime(true);
            $read_declarations = static fn (): array => (new LCDeclarationReader())->read($event->getComposer());

            if ($event->getName() === 'pre-autoload-dump') {
                $packages = StandardComposerIO::tree('Declaration validation', static function () use ($read_declarations): array {
                    $validation_started_at = hrtime(true);

                    try {
                        $packages = $read_declarations();
                    } catch (Throwable $exception) {
                        StandardComposerIO::result(
                            'FAIL',
                            sprintf('extra.webkernel schema validation failed: %s', $exception->getMessage()),
                            StandardComposerIO::duration(hrtime(true) - $validation_started_at),
                        );
                        throw $exception;
                    }

                    StandardComposerIO::result(
                        'PASS',
                        'extra.webkernel declarations match the Lifecycle schema; autoloader generation may proceed.',
                        StandardComposerIO::duration(hrtime(true) - $validation_started_at),
                    );

                    return $packages;
                });
            } else {
                $packages = $read_declarations();
            }

            (new LCEventDispatcher())->dispatch(
                $event->getName(),
                $event,
                $packages,
                $event_started_at,
            );
        } finally {
            StandardComposerIO::restore($previous_io);
        }
    }
}
