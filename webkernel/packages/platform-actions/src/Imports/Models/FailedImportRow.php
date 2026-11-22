<?php

namespace Webkernel\Platform\Actions\Imports\Models;

use Webkernel\ORM\Builder;
use Webkernel\ORM\Model;
use Webkernel\ORM\Prunable;
use Webkernel\ORM\Relations\BelongsTo;

/**
 * @property array<string, mixed> $data
 * @property string | null $validation_error
 * @property-read Import $import
 */
class FailedImportRow extends Model
{
    use Prunable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    protected $guarded = [];

    public function import(): BelongsTo
    {
        return $this->belongsTo(app(Import::class)::class);
    }

    public function prunable(): Builder
    {
        return static::where(
            'created_at',
            '<=',
            now()->subMonth(),
        );
    }
}
