<?php declare(strict_types=1);

namespace Webkernel\Platform\Panel\Concerns;

use InvalidArgumentException;
use LogicException;

trait HasScope
{
    protected ?string $scope = null;

    protected ?string $definitionProvider = null;

    /** @internal The route compiler records this class in compiled middleware metadata. */
    public function definitionProvider(string $provider): static
    {
        $this->definitionProvider = $provider;
        return $this;
    }

    public function getDefinitionProvider(): ?string { return $this->definitionProvider; }

    public function scope(string $scope): static
    {
        if (! in_array($scope, ['platform', 'module'], true)) {
            throw new InvalidArgumentException('A panel scope must be platform or module.');
        }
        $this->scope = $scope;
        return $this;
    }

    public function getScope(): string
    {
        return $this->scope ?? throw new LogicException('Every Webkernel panel must declare its scope.');
    }
}
