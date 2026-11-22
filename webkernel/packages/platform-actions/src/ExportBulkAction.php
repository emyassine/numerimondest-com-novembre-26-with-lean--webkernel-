<?php

namespace Webkernel\Platform\Actions;

use Webkernel\Platform\Actions\Concerns\CanExportRecords;

class ExportBulkAction extends BulkAction
{
    use CanExportRecords {
        setUp as baseSetUp;
    }

    protected function setUp(): void
    {
        $this->baseSetUp();

        $this->fetchSelectedRecords(false);
    }
}
