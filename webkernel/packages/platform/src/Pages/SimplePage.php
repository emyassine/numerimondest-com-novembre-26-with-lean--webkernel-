<?php

namespace Webkernel\Platform\Pages;

abstract class SimplePage extends BasePage
{
    use Concerns\HasMaxWidth;
    use Concerns\HasTopbar;

    protected string $view = 'webkernel-platform::pages.simple';

    protected static string $layout = 'webkernel-platform::components.layout.simple';

    protected function getLayoutData(): array
    {
        return [
            'hasTopbar' => $this->hasTopbar(),
            'maxContentWidth' => $maxContentWidth = $this->getMaxWidth() ?? $this->getMaxContentWidth(),
            'maxWidth' => $maxContentWidth,
        ];
    }

    public function hasLogo(): bool
    {
        return true;
    }
}
