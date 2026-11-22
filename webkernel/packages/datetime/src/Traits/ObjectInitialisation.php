<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

/**
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webkernel\DateTime\Traits;

trait ObjectInitialisation
{
    /**
     * True when parent::__construct has been called.
     *
     * Can be an int (starting from version 3.13.3, was a string in previous version, can come from unserializing
     * objects serialized by an older version, but this property then get overwriten during __construct).
     *
     * @var string|int
     */
    protected $constructedObjectId;
}
