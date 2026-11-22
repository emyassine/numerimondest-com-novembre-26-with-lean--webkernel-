<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Utilities;

use \Webkernel\DateTime\Carbon as BaseCarbon;
use \Webkernel\DateTime\CarbonImmutable as BaseCarbonImmutable;
use \Webkernel\Utilities\Traits\Conditionable;
use \Webkernel\Utilities\Traits\Dumpable;
use \Webkernel\Identifiers\Uuid\Uuid;
use Symfony\Component\Uid\Ulid;

class Carbon extends BaseCarbon
{
    use Conditionable, Dumpable;

    /**
     * {@inheritdoc}
     */
    public static function setTestNow(mixed $testNow = null): void
    {
        BaseCarbon::setTestNow($testNow);
        BaseCarbonImmutable::setTestNow($testNow);
    }

    /**
     * Create a Carbon instance from a given ordered UUID or ULID.
     */
    public static function createFromId(Uuid|Ulid|string $id): static
    {
        if (is_string($id)) {
            $id = Ulid::isValid($id) ? Ulid::fromString($id) : Uuid::fromString($id);
        }

        return static::createFromInterface($id->getDateTime());
    }

    /**
     * Get the current date / time plus a given amount of time.
     */
    public function plus(
        int $years = 0,
        int $months = 0,
        int $weeks = 0,
        int $days = 0,
        int $hours = 0,
        int $minutes = 0,
        int $seconds = 0,
        int $microseconds = 0,
        ?bool $overflow = null
    ): static {
        return $this->add('years', $years, $overflow)
            ->add('months', $months, $overflow)
            ->add("
                $weeks weeks $days days
                $hours hours $minutes minutes $seconds seconds $microseconds microseconds
            ");
    }

    /**
     * Get the current date / time minus a given amount of time.
     */
    public function minus(
        int $years = 0,
        int $months = 0,
        int $weeks = 0,
        int $days = 0,
        int $hours = 0,
        int $minutes = 0,
        int $seconds = 0,
        int $microseconds = 0,
        ?bool $overflow = null
    ): static {
        return $this->sub('years', $years, $overflow)
            ->sub('months', $months, $overflow)
            ->sub("
                $weeks weeks $days days
                $hours hours $minutes minutes $seconds seconds $microseconds microseconds
            ");
    }
}
