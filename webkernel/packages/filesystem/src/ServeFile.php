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

/** Download handler; the Webkernel route supplies authorization/signature validation. */
class ServeFile
{
    /** @param $authorize Closure(Request, string): bool */
    public function __construct(private FilesystemAdapter $disk, private Closure $authorize)
    {
    }

    public function __invoke(Request $request, string $path): Response
    {
        if (! ($this->authorize)($request, $path)) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }
        try {
            if (! $this->disk->fileExists($path)) {
                return new Response('', Response::HTTP_NOT_FOUND);
            }
            $response = $this->disk->serve($request, $path);
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox");
            return $response;
        } catch (PathTraversalDetected) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }
    }
}
