<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Platform\Runtime;

use JsonException;
use RuntimeException;

/** Sends one bounded request to the private PHP worker socket. */
final readonly class PhpWorkerClient
{
    public function __construct(private string $socket_path, private float $timeout_seconds = 0.5)
    {
    }

    public function send(WorkerRequest $request): WorkerResponse
    {
        if (! is_file($this->socket_path) || filetype($this->socket_path) !== 'socket') {
            throw new RuntimeException('The PHP worker socket is unavailable.');
        }
        $socket = @stream_socket_client('unix://'.$this->socket_path, $error_code, $error_message, $this->timeout_seconds);
        if (! is_resource($socket)) {
            throw new RuntimeException('Unable to connect to the PHP worker socket: '.$error_message, $error_code);
        }
        stream_set_timeout($socket, (int) $this->timeout_seconds, (int) (($this->timeout_seconds - (int) $this->timeout_seconds) * 1_000_000));
        try {
            $line = json_encode([
                'method' => $request->method,
                'path' => $request->path,
                'headers' => $request->headers,
                'body' => base64_encode($request->body),
                'token' => $request->token,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $exception) {
            fclose($socket);
            throw new RuntimeException('Unable to encode PHP worker request.', previous: $exception);
        }
        if (! $this->writeAll($socket, $line."\n")) {
            fclose($socket);
            throw new RuntimeException('The PHP worker did not accept the request envelope.');
        }
        $response_line = fgets($socket, 22_500_000);
        $status = stream_get_meta_data($socket)['timed_out'] ?? false;
        fclose($socket);
        if (! is_string($response_line) || $status === true) {
            throw new RuntimeException('The PHP worker response timed out or was incomplete.');
        }
        try {
            $response = json_decode($response_line, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('The PHP worker returned an invalid response envelope.', previous: $exception);
        }
        if (! is_array($response)
            || ! is_int($response['status'] ?? null)
            || ! is_array($response['headers'] ?? null)
            || ! is_string($response['body'] ?? null)) {
            throw new RuntimeException('The PHP worker returned an invalid response envelope.');
        }
        $body = base64_decode($response['body'], true);
        if (! is_string($body)) {
            throw new RuntimeException('The PHP worker returned an invalid response body.');
        }
        return new WorkerResponse($response['status'], $response['headers'], $body);
    }

    /** @param resource $socket */
    private function writeAll(mixed $socket, string $contents): bool
    {
        $offset = 0;
        $length = strlen($contents);
        while ($offset < $length) {
            $written = fwrite($socket, substr($contents, $offset));
            if (! is_int($written) || $written < 1) {
                return false;
            }
            $offset += $written;
        }
        return true;
    }
}
