<?php

namespace Webkernel\Platform\Support;

use Closure;
use Webkernel\Platform\Support\Concerns\EvaluatesClosures;

class TimezoneManager
{
    use EvaluatesClosures;

    protected string | Closure | null $timezone = null;

    public function set(string | Closure | null $timezone = null): void
    {
        $this->timezone = $timezone;
    }

    public function get(): string
    {
        return $this->evaluate($this->timezone) ?? config('app.timezone');
    }
}
