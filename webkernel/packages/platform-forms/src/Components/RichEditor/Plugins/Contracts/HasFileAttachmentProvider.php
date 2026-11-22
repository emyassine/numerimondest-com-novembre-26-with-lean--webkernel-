<?php

namespace Webkernel\Platform\Forms\Components\RichEditor\Plugins\Contracts;

use Webkernel\Platform\Forms\Components\RichEditor\FileAttachmentProviders\Contracts\FileAttachmentProvider;

interface HasFileAttachmentProvider
{
    public function getFileAttachmentProvider(): ?FileAttachmentProvider;
}
