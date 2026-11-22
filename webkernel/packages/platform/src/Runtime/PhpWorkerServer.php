<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Runtime;

use Closure;
use JsonException;
use RuntimeException;

/** Serves compiled requests over a private Unix socket until a bounded exit condition. */
final class PhpWorkerServer
{
    /** @param Closure(WorkerRequest, array{sub: string, acl: int, exp: int}): WorkerResponse $dispatch */
    public function serve(
        string $socket_path,
        string $signing_key,
        Closure $dispatch,
        int $max_requests = 500,
        int $idle_timeout_seconds = 60,
    ): void {
        if (strlen($signing_key) < 32 || $max_requests < 1 || $idle_timeout_seconds < 1) {
            throw new RuntimeException('Invalid PHP worker configuration.');
        }
        @unlink($socket_path);
        $server = @stream_socket_server('unix://'.$socket_path, $error_code, $error_message, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
        if (! is_resource($server)) {
            throw new RuntimeException('Unable to open PHP worker socket: '.$error_message, $error_code);
        }
        chmod($socket_path, 0600);
        $served = 0;
        $last_request_at = time();
        try {
            while ($served < $max_requests && time() - $last_request_at < $idle_timeout_seconds) {
                $client = @stream_socket_accept($server, 1);
                if (! is_resource($client)) {
                    continue;
                }
                $last_request_at = time();
                stream_set_timeout($client, 2);
                $response = $this->handle($client, $signing_key, $dispatch);
                $this->writeResponse($client, $response);
                fclose($client);
                $served++;
            }
        } finally {
            fclose($server);
            @unlink($socket_path);
        }
    }

    /** @param resource $client @param Closure(WorkerRequest, array{sub: string, acl: int, exp: int}): WorkerResponse $dispatch */
    private function handle(mixed $client, string $signing_key, Closure $dispatch): WorkerResponse
    {
        $line = fgets($client, 12_000_000);
        if (! is_string($line) || strlen($line) > 11_500_000) {
            return new WorkerResponse(400, ['content-type' => 'text/plain; charset=utf-8'], 'Bad Request');
        }
        try {
            $data = json_decode($line, true, 16, JSON_THROW_ON_ERROR);
            if (! is_array($data)
                || ! is_string($data['method'] ?? null)
                || ! is_string($data['path'] ?? null)
                || ! is_array($data['headers'] ?? null)
                || ! is_string($data['body'] ?? null)
                || ! is_string($data['token'] ?? null)) {
                return new WorkerResponse(400, ['content-type' => 'text/plain; charset=utf-8'], 'Bad Request');
            }
            $body = base64_decode($data['body'], true);
            if (! is_string($body)) {
                return new WorkerResponse(400, ['content-type' => 'text/plain; charset=utf-8'], 'Bad Request');
            }
            $request = new WorkerRequest($data['method'], $data['path'], $data['headers'], $body, $data['token']);
        } catch (JsonException|\InvalidArgumentException) {
            return new WorkerResponse(400, ['content-type' => 'text/plain; charset=utf-8'], 'Bad Request');
        }
        $claims = SignedToken::verify($request->token, $signing_key);
        if ($claims === null) {
            return new WorkerResponse(401, ['content-type' => 'text/plain; charset=utf-8'], 'Unauthorized');
        }
        return $dispatch($request, $claims);
    }

    /** @param resource $client */
    private function writeResponse(mixed $client, WorkerResponse $response): void
    {
        try {
            $line = json_encode([
                'status' => $response->status,
                'headers' => $response->headers,
                'body' => base64_encode($response->body),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode PHP worker response.', previous: $exception);
        }
        $this->writeAll($client, $line."\n");
    }

    /** @param resource $stream */
    private function writeAll(mixed $stream, string $contents): void
    {
        $offset = 0;
        $length = strlen($contents);
        while ($offset < $length) {
            $written = fwrite($stream, substr($contents, $offset));
            if (! is_int($written) || $written < 1) {
                throw new RuntimeException('The PHP worker could not write the complete response.');
            }
            $offset += $written;
        }
    }
}
