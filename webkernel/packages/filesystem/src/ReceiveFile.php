<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from Laravel; see LICENSE-LARAVEL.md.

namespace Webkernel\Filesystem;

use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkernel\Filesystem\FilesystemAdapter;
use Webkernel\Filesystem\Storage\PathTraversalDetected;

/** Upload handler; the Webkernel route supplies authorization/signature validation. */
class ReceiveFile
{
    /** @param $authorize Closure(Request, string): bool */
    public function __construct(private FilesystemAdapter $disk, private Closure $authorize)
    {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $path): Response
    {
        if (! ($this->authorize)($request, $path)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }
        try {
            $stream = $request->getContent(true);
            try {
                $stored = $this->disk->writeStream($path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
            return new Response('', $stored ? Response::HTTP_NO_CONTENT : Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (PathTraversalDetected) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }
    }
}
