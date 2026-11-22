<?php

namespace Webkernel\Platform\Schemas\Components\Contracts;

use Webkernel\ORM\Model;
use Webkernel\ORM\Relations\BelongsTo;
use Webkernel\ORM\Relations\HasOne;
use Webkernel\ORM\Relations\MorphOne;

interface CanEntangleWithSingularRelationships
{
    public function cachedExistingRecord(?Model $record): static;

    public function clearCachedExistingRecord(): void;

    public function fillFromRelationship(): void;

    public function getCachedExistingRecord(): ?Model;

    /**
     * @return class-string<Model>|null
     */
    public function getRelatedModel(): ?string;

    public function getRelationship(): BelongsTo | HasOne | MorphOne | null;

    public function getRelationshipName(): ?string;

    public function hasRelationship(): bool;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mutateRelationshipDataBeforeFill(array $data): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mutateRelationshipDataBeforeCreate(array $data): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mutateRelationshipDataBeforeSave(array $data): array;
}
