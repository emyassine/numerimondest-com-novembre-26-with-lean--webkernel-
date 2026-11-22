<?php

namespace Webkernel\Platform\Schemas\Concerns;

trait RestrictsFileUploadsToSchemaComponents
{
    public function shouldRestrictFileUploadsToSchemaComponents(): bool
    {
        return true;
    }
}
