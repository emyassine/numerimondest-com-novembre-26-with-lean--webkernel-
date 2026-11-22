<?php

namespace Webkernel\Platform\Schemas\Components\StateCasts;

use Webkernel\Platform\Schemas\Components\StateCasts\Contracts\StateCast;
use Webkernel\Utilities\Arr;
use Webkernel\Utilities\Str;

class FileUploadStateCast implements StateCast
{
    public function __construct(
        protected bool $isMultiple = false,
    ) {}

    public function get(mixed $state): mixed
    {
        if ($this->isMultiple) {
            return array_values(Arr::wrap($state));
        }

        if (! is_array($state)) {
            return $state;
        }

        return Arr::first($state);
    }

    public function set(mixed $state): mixed
    {
        $newState = [];

        foreach (Arr::wrap($state) as $key => $file) {
            if (blank($file)) {
                continue;
            }

            if (is_numeric($key)) {
                $key = (string) Str::uuid();
            }

            $newState[$key] = $file;
        }

        return $newState;
    }
}
