<?php

namespace Webkernel\Platform\Support\Concerns;

use Webkernel\View\Contracts\View;
use Webkernel\View\ViewResult;

trait CanBeLazy
{
    protected static bool $isLazy = true;

    protected ?string $placeholderHeight = null;

    public static function isLazy(): bool
    {
        return static::$isLazy;
    }

    public function placeholder(): View|ViewResult
    {
        return \Webkernel\View\View::resolve(
            'webkernel::components.loading-section',
            [
                'height' => $this->getPlaceholderHeight(),
                ...$this->getPlaceholderData(),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getPlaceholderData(): array
    {
        return [
            //
        ];
    }

    public function getPlaceholderHeight(): ?string
    {
        return $this->placeholderHeight;
    }
}
