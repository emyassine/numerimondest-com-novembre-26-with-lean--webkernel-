<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Runtime;

use Closure;
use RuntimeException;

/** Uses the worker when healthy and serves the current request in process on failure. */
final readonly class PhpWorkerGateway
{
    public function __construct(private PhpWorkerClient $client)
    {
    }

    /** @param Closure(): WorkerResponse $in_process @param Closure(): void $respawn */
    public function handle(WorkerRequest $request, Closure $in_process, Closure $respawn): WorkerResponse
    {
        try {
            return $this->client->send($request);
        } catch (RuntimeException $exception) {
            $response = $in_process();
            try {
                $respawn();
            } catch (RuntimeException $spawn_exception) {
                error_log('Optional PHP worker restart failed: '.$spawn_exception->getMessage());
            }
            return $response;
        }
    }
}
