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

use function count;


/**
 * Accumulates errors and warnings during schema validation and tracks the current path.
 */
final class Context
{
	public bool $skipDefaults = false;

	/**
	 * @internal path at which a null is not coerced to an empty value, because the enclosing anyOf() accepts it
	 * @var ?list<int|string>
	 */
	public ?array $keepNullAt = null;

	/** @var list<int|string> */
	public array $path = [];

	public bool $isKey = false;

	/** @var list<Message> */
	public array $errors = [];

	/** @var list<Message> */
	public array $warnings = [];

	/** @var list<array{DynamicParameter, string, list<int|string>}> */
	public array $dynamics = [];


	/** @param  array<string, mixed>  $variables */
	public function addError(string $message, string $code, array $variables = []): Message
	{
		$variables['isKey'] = $this->isKey;
		return $this->errors[] = new Message($message, $code, $this->path, $variables);
	}


	/** @param  array<string, mixed>  $variables */
	public function addWarning(string $message, string $code, array $variables = []): Message
	{
		$variables['isKey'] = $this->isKey;
		return $this->warnings[] = new Message($message, $code, $this->path, $variables);
	}


	/**
	 * Returns a closure that returns true as long as no new errors have been added since the call.
	 * @return \Closure(): bool
	 */
	public function createChecker(): \Closure
	{
		$count = count($this->errors);
		return fn(): bool => $count === count($this->errors);
	}
}
