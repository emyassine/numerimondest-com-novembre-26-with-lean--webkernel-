<?php

declare(strict_types=1);

namespace Webkernel\Utilities\JsonSchema;

enum DraftIdentifiers: string
{
    case Draft3 = 'http://json-schema.org/draft-03/schema#';
    case Draft4 = 'http://json-schema.org/draft-04/schema#';
    case Draft6 = 'http://json-schema.org/draft-06/schema#';
    case Draft7 = 'http://json-schema.org/draft-07/schema#';
    case Draft201909 = 'https://json-schema.org/draft/2019-09/schema';
    case Draft202012 = 'https://json-schema.org/draft/2020-12/schema';

    /** @var array<string, string> */
    private const MAPPING = [
        self::Draft3->value => 'draft03',
        self::Draft4->value => 'draft04',
        self::Draft6->value => 'draft06',
        self::Draft7->value => 'draft07',
        self::Draft201909->value => 'draft2019-09',
        self::Draft202012->value => 'draft2020-12',
    ];

    private const FALLBACK_MAPPING = [
        'draft3' => self::Draft3->value,
        'draft4' => self::Draft4->value,
        'draft6' => self::Draft6->value,
        'draft7' => self::Draft7->value,
    ];

    public function toConstraintName(): string
    {
        return self::MAPPING[$this->value];
    }

    public static function fromConstraintName(string $name): DraftIdentifiers
    {
        $reverseMap = array_flip(self::MAPPING);
        if (!array_key_exists($name, $reverseMap)) {
            if (array_key_exists($name, self::FALLBACK_MAPPING)) {
                return DraftIdentifiers::from(self::FALLBACK_MAPPING[$name]);
            }
            throw new \InvalidArgumentException("$name is not a valid constraint name.");
        }

        return DraftIdentifiers::from($reverseMap[$name]);
    }

    public function withoutFragment(): string
    {
        return rtrim($this->value, '#');
    }
}
