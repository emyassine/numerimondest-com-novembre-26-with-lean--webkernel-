<?php

namespace Webkernel\Platform\Resources\Events;

use Webkernel\Platform\Resources\Pages\Page;
use Webkernel\ORM\Model;
use \Webkernel\Events\Dispatchable;

class RecordSaved
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        protected Model $record,
        protected array $data,
        protected Page $page,
    ) {}

    public function getRecord(): Model
    {
        return $this->record;
    }

    public function getPage(): Page
    {
        return $this->page;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
