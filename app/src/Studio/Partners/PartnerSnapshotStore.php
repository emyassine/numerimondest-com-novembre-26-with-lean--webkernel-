<?php declare(strict_types=1);

namespace App\Studio\Partners;

/** Stores one validated catalogue snapshot per partner using atomic replacement. */
final class PartnerSnapshotStore
{
    public function __construct(private readonly string $directory) {}

    /** @return array{etag:string,payload:array}|null */
    public function read(string $partnerId): ?array
    {
        $path = $this->path($partnerId);
        if (!is_file($path)) return null;
        $snapshot = json_decode((string) file_get_contents($path), true);
        if (!is_array($snapshot) || !is_string($snapshot['etag'] ?? null) || !is_array($snapshot['payload'] ?? null)) return null;
        return $snapshot;
    }

    /** @param array<string,mixed> $payload */
    public function write(string $partnerId, array $payload, string $etag): void
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Unable to create partner snapshot directory.');
        }
        $path = $this->path($partnerId);
        $temp = $path.'.'.bin2hex(random_bytes(6)).'.tmp';
        $encoded = json_encode(['etag'=>$etag,'payload'=>$payload], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        if (file_put_contents($temp, $encoded, LOCK_EX) === false || !rename($temp, $path)) {
            @unlink($temp);
            throw new \RuntimeException('Unable to publish partner snapshot atomically.');
        }
        @chmod($path, 0600);
    }

    private function path(string $partnerId): string
    {
        if (!preg_match('/^[a-z0-9-]{1,80}$/', $partnerId)) throw new \InvalidArgumentException('Invalid partner identifier.');
        return rtrim($this->directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$partnerId.'.json';
    }
}
