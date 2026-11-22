<?php

namespace Webkernel\Platform\Forms\Components\Concerns;

use Closure;

trait HasUploadingMessage
{
    protected string | Closure | null $uploadingMessage = null;

    public function uploadingMessage(string | Closure | null $message): static
    {
        $this->uploadingMessage = $message;

        return $this;
    }

    public function getUploadingMessage(): string
    {
        return $this->evaluate($this->uploadingMessage) ?? __('webkernel::components/button.messages.uploading_file');
    }
}
