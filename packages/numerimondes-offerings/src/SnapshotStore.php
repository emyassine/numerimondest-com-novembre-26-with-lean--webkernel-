<?php declare(strict_types=1);

namespace Numerimondes\Offerings;

/** Partner-isolated snapshot storage with atomic publication and restrictive permissions. */
final class SnapshotStore
{
    public function __construct(private readonly string $directory) {}

    /** @return array{etag:string,payload:array}|null */
    public function read(string $partnerId): ?array
    {
        $path = $this->path($partnerId);
        if (!is_file($path)) return null;
        $raw = file_get_contents($path);
        if ($raw === false) return null;
        try { $stored = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { return null; }
        if (!is_array($stored) || !is_string($stored['etag'] ?? null) || !is_array($stored['payload'] ?? null)) return null;
        try { CatalogueClient::validatePayload($stored['payload']); }
        catch (\UnexpectedValueException) { return null; }
        return $stored;
    }

    public function write(string $partnerId, array $payload, string $etag): void
    {
        CatalogueClient::validatePayload($payload);
        if ($etag === '' || str_contains($etag, "\r") || str_contains($etag, "\n")) throw new \InvalidArgumentException('Invalid ETag.');
        if (!is_dir($this->directory) && !mkdir($this->directory, 0700, true) && !is_dir($this->directory)) throw new \RuntimeException('Unable to create snapshot directory.');
        @chmod($this->directory, 0700);
        $path = $this->path($partnerId);
        $temporary = $path.'.'.bin2hex(random_bytes(12)).'.tmp';
        $contents = json_encode(['etag' => $etag, 'payload' => $payload], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $handle = fopen($temporary, 'xb');
        if ($handle === false) throw new \RuntimeException('Unable to create temporary catalogue snapshot.');
        try {
            if (!flock($handle, LOCK_EX) || fwrite($handle, $contents) !== strlen($contents) || !fflush($handle)) throw new \RuntimeException('Unable to write catalogue snapshot.');
        } finally { fclose($handle); }
        @chmod($temporary, 0600);
        if (!rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('Unable to publish snapshot atomically.'); }
        @chmod($path, 0600);
    }

    private function path(string $partnerId): string
    {
        if (!preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/', $partnerId)) throw new \InvalidArgumentException('Invalid partner identifier.');
        return rtrim($this->directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$partnerId.'.json';
    }
}
