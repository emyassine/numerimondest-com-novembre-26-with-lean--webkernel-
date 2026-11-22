<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from AnourValar Eloquent Serialize and Laravel Serializable Closure; see the accompanying LICENSE-*.md files.
 //> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\ORM\Serialization;

use Webkernel\ORM\Builder;
use Webkernel\ORM\Relations\Relation;
use Webkernel\ORM\Serialization\Closure\SerializableClosure;
use RuntimeException;

use function config;

/** Serializes an ORM query for deferred export jobs. */
final class QuerySerializer
{
    public static function serialize(Builder|Relation $query): string
    {
        $secret = self::configureSigner();
        $payload = (new Service())->serialize($query);

        if ($secret === null && str_contains($payload, SerializableClosure::class)) {
            throw new RuntimeException('Set app.secret to serialize recursive eager-load closures safely.');
        }

        return $payload;
    }

    public static function unserialize(string $payload): Builder
    {
        $secret = self::configureSigner();

        if ($secret === null && str_contains($payload, SerializableClosure::class)) {
            throw new RuntimeException('Set app.secret to unserialize recursive eager-load closures safely.');
        }

        return (new Service())->unserialize($payload);
    }

    private static function configureSigner(): ?string
    {
        $secret = config('app.secret');
        $secret = is_string($secret) && $secret !== '' ? $secret : null;

        SerializableClosure::setSecretKey($secret);

        return $secret;
    }
}
