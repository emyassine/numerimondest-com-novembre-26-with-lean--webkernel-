<?php

namespace Webkernel\Platform\Schemas\Contracts;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Schemas\Components\Component;
use Webkernel\Platform\Schemas\Schema;
use Webkernel\Platform\Support\Contracts\TranslatableContentDriver;

interface HasSchemas
{
    public function makePlatformTranslatableContentDriver(): ?TranslatableContentDriver;

    public function getOldSchemaState(string $statePath): mixed;

    /**
     * @param  array<Component>  $skipComponentsChildContainersWhileSearching
     */
    public function getSchemaComponent(string $key, bool $withHidden = false, array $skipComponentsChildContainersWhileSearching = []): Component | Action | ActionGroup | null;

    public function getSchema(string $name): ?Schema;

    public function currentlyValidatingSchema(?Schema $schema): void;

    public function getDefaultTestingSchemaName(): ?string;
}
