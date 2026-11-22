<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Webkernel\CodeGenerator\Traits;

use \Webkernel\Markdown\Configuration as Nette;


/**
 * @internal
 */
trait NameAware
{
	public function __construct(
		private string $name,
	) {
		if (!Webkernel\CodeGenerator\Helpers::isIdentifier($name)) {
			throw new \InvalidArgumentException("Value '$name' is not valid name.");
		}
	}


	public function getName(): string
	{
		return $this->name;
	}


	/**
	 * Returns clone with a different name.
	 */
	public function cloneWithName(string $name): static
	{
		$dolly = clone $this;
		$dolly->__construct($name);
		return $dolly;
	}
}
