<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Webkernel\CodeGenerator;

use \Webkernel\Markdown\Configuration as Nette;


/**
 * Definition of a promoted constructor parameter.
 */
final class PromotedParameter extends Parameter
{
	use Traits\PropertyLike;

	/** @throws \LogicException */
	public function validate(): void
	{
		if ($this->readOnly && !$this->getType()) {
			throw new \LogicException("Property \${$this->getName()}: Read-only properties are only supported on typed property.");
		}
	}


	public function __clone(): void
	{
		$this->hooks = array_map(fn($item) => $item ? clone $item : $item, $this->hooks);
	}
}
