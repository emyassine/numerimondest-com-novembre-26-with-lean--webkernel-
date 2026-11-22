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

namespace Webkernel\Markdown\Configuration\Utilities;

use function is_string;


/**
 * ReflectionMethod preserving the original class name.
 * @internal
 */
final class ReflectionMethod extends \ReflectionMethod
{
	/** @var \ReflectionClass<object> */
	private readonly \ReflectionClass $originalClass;


	/** @param  class-string|object  $objectOrMethod */
	public function __construct(object|string $objectOrMethod, ?string $method = null)
	{
		if (is_string($objectOrMethod) && str_contains($objectOrMethod, '::')) {
			[$objectOrMethod, $method] = explode('::', $objectOrMethod, 2);
		}
		parent::__construct($objectOrMethod, $method);
		$this->originalClass = new \ReflectionClass($objectOrMethod);
	}


	/** @return \ReflectionClass<object> */
	public function getOriginalClass(): \ReflectionClass
	{
		return $this->originalClass;
	}
}
