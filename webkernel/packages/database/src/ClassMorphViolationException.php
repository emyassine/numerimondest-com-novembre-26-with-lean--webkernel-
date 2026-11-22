<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Database;

use RuntimeException;

final class ClassMorphViolationException extends RuntimeException
{
    public string $model;

    public function __construct(object|string $model)
    {
        $this->model = is_object($model) ? $model::class : $model;
        parent::__construct("No morph map defined for model [{$this->model}].");
    }
}
