<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.
// Adapted from AnourValar Eloquent Serialize and Laravel Serializable Closure; see the accompanying LICENSE-*.md files.
namespace Webkernel\ORM\Serialization;

class Package
{
    /**
     * $var array
     */
    private $data;

    /**
     * @param array $data
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param string|null $key
     * @return mixed
     */
    public function get(?string $key = null)
    {
        if (is_null($key)) {
            return $this->data;
        }

        return ($this->data[$key] ?? null);
    }
}
