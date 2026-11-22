<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Webkernel\Markdown\Configuration\Schema;


/**
 * Defines the contract for schema elements used in data validation and normalization.
 */
interface Schema
{
	/**
	 * Applies pre-processing transformations to the raw input value (e.g., via before() hooks).
	 * @return mixed
	 */
	function normalize(mixed $value, Context $context);

	/**
	 * Merges two normalized values, with $value taking priority over $base.
	 * @return mixed
	 */
	function merge(mixed $value, mixed $base);

	/**
	 * Validates the value and applies defaults, transforms, and assertions.
	 * @return mixed
	 */
	function complete(mixed $value, Context $context);

	/**
	 * Returns the default value, or adds a missing-item error if the field is required.
	 * @return mixed
	 */
	function completeDefault(Context $context);
}
