<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

/**
 * This file is part of the ramsey/uuid library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Webkernel\Identifiers\Uuid\Rfc4122;

use \Webkernel\Identifiers\Uuid\Codec\CodecInterface;
use \Webkernel\Identifiers\Uuid\Converter\NumberConverterInterface;
use \Webkernel\Identifiers\Uuid\Converter\TimeConverterInterface;
use \Webkernel\Identifiers\Uuid\Exception\InvalidArgumentException;
use \Webkernel\Identifiers\Uuid\Rfc4122\FieldsInterface as Rfc4122FieldsInterface;
use \Webkernel\Identifiers\Uuid\Uuid;

/**
 * Custom format, or version 8, UUIDs provide an RFC-compatible format for experimental or vendor-specific uses
 *
 * The only requirement for version 8 UUIDs is that the version and variant bits must be set. Otherwise, implementations
 * are free to set the other bits according to their needs. As a result, the uniqueness of version 8 UUIDs is
 * implementation-specific and should not be assumed.
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.8 RFC 9562, 5.8. UUID Version 8
 *
 * @immutable
 */
final class UuidV8 extends Uuid implements UuidInterface
{
    /**
     * Creates a version 8 (custom format) UUID
     *
     * @param Rfc4122FieldsInterface $fields The fields from which to construct a UUID
     * @param NumberConverterInterface $numberConverter The number converter to use for converting hex values to/from integers
     * @param CodecInterface $codec The codec to use when encoding or decoding UUID strings
     * @param TimeConverterInterface $timeConverter The time converter to use for converting timestamps extracted from a
     *     UUID to unix timestamps
     */
    public function __construct(
        Rfc4122FieldsInterface $fields,
        NumberConverterInterface $numberConverter,
        CodecInterface $codec,
        TimeConverterInterface $timeConverter,
    ) {
        if ($fields->getVersion() !== Uuid::UUID_TYPE_CUSTOM) {
            throw new InvalidArgumentException(
                'Fields used to create a UuidV8 must represent a version 8 (custom format) UUID',
            );
        }

        parent::__construct($fields, $numberConverter, $codec, $timeConverter);
    }
}
