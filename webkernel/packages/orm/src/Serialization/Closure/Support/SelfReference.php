<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from AnourValar Eloquent Serialize and Laravel Serializable Closure; see the accompanying LICENSE-*.md files.
namespace Webkernel\ORM\Serialization\Closure\Support;

class SelfReference
{
    /**
     * The unique hash representing the object.
     *
     * @var string|int
     */
    public $hash;

    /**
     * Creates a new self reference instance.
     *
     * @param  string|int  $hash
     * @return void
     */
    public function __construct($hash)
    {
        $this->hash = $hash;
    }
}
