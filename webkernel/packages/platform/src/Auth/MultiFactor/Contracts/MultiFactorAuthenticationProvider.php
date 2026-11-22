<?php

namespace Webkernel\Platform\Auth\MultiFactor\Contracts;

use Webkernel\Platform\Actions\Action;
use Webkernel\Platform\Actions\ActionGroup;
use Webkernel\Platform\Schemas\Components\Component;
use \Webkernel\Accounts\Contracts\Authenticatable;

interface MultiFactorAuthenticationProvider
{
    public function isEnabled(Authenticatable $user): bool;

    public function getId(): string;

    public function getLoginFormLabel(): string;

    /**
     * @return array<Component| Action>
     */
    public function getManagementSchemaComponents(): array;

    /**
     * @return array<Component | Action | ActionGroup>
     */
    public function getChallengeFormComponents(Authenticatable $user): array;
}
