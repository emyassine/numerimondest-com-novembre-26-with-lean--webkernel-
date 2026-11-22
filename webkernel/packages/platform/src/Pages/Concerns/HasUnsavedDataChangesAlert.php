<?php

namespace Webkernel\Platform\Pages\Concerns;

use Webkernel\Platform\Facades\Platform;
use Webkernel\Liveview\Attributes\Locked;

trait HasUnsavedDataChangesAlert
{
    #[Locked]
    public string $savedDataHash;

    protected ?bool $hasUnsavedDataChangesAlert = null;

    public function mountHasUnsavedDataChangesAlert(): void
    {
        $this->rememberData();
    }

    protected function rememberData(): void
    {
        if (! $this->hasUnsavedDataChangesAlert()) {
            return;
        }

        $this->savedDataHash = md5((string) str(json_encode($this->data, JSON_UNESCAPED_UNICODE))->replace('\\', ''));
    }

    protected function hasUnsavedDataChangesAlert(): bool
    {
        return $this->hasUnsavedDataChangesAlert ?? Platform::hasUnsavedChangesAlerts();
    }
}
