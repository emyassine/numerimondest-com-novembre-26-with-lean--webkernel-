<?php

namespace Webkernel\Platform\Panel\Concerns;

use Closure;
use Webkernel\Platform\AvatarProviders\UiAvatarsProvider;

trait HasAvatars
{
    protected string | Closure $defaultAvatarProvider = UiAvatarsProvider::class;

    public function defaultAvatarProvider(string | Closure $provider): static
    {
        $this->defaultAvatarProvider = $provider;

        return $this;
    }

    public function getDefaultAvatarProvider(): string
    {
        return $this->evaluate($this->defaultAvatarProvider);
    }
}
