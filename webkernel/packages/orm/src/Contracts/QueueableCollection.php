<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\ORM\Contracts;

interface QueueableCollection
{
    /**
     * Get the type of the entities being queued.
     *
     * @return string|null
     */
    public function getQueueableClass();

    /**
     * Get the identifiers for all of the entities.
     *
     * @return array<int, mixed>
     */
    public function getQueueableIds();

    /**
     * Get the relationships of the entities being queued.
     *
     * @return array<int, string>
     */
    public function getQueueableRelations();

    /**
     * Get the connection of the entities being queued.
     *
     * @return string|null
     */
    public function getQueueableConnection();
}
