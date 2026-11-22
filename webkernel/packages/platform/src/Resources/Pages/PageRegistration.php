<?php

namespace Webkernel\Platform\Resources\Pages;

use Closure;
use Webkernel\Platform\Panel;
use \Webkernel\Route\CurrentRoute as Route;

class PageRegistration
{
    public function __construct(
        protected string $page,
        protected Closure $route,
    ) {}

    public function registerRoute(Panel $panel): ?Route
    {
        return ($this->route)($panel);
    }

    public function getPage(): string
    {
        return $this->page;
    }
}
