<?php

namespace Webkernel\Platform\Actions\Testing;

use Webkernel\ORM\Serialization\QuerySerializer;
use Closure;
use Webkernel\Platform\Actions\Exports\Enums\Contracts\ExportFormat;
use Webkernel\Platform\Actions\Exports\ExportDispatcher;
use Webkernel\Platform\Actions\Exports\Exporter;
use Webkernel\Platform\Actions\Exports\Models\Export;
use Webkernel\ORM\Builder;
use Webkernel\ORM\Model;
use \PHPUnit\Framework\Assert;

class ExportFake extends ExportDispatcher
{
    /**
     * @var array<array{export: Export, serializedQuery: string, columnMap: array<string, string>, options: array<string, mixed>, formats: array<ExportFormat>, records: array<mixed> | null}>
     */
    protected array $dispatched = [];

    public function dispatch(
        Export $export,
        string $serializedQuery,
        array $columnMap,
        array $options,
        array $formats,
        ?array $records,
        string $job,
        int $chunkSize,
        ?string $jobQueue,
        ?string $jobConnection,
        ?string $jobBatchName,
        string $authGuard,
    ): void {
        $this->dispatched[] = compact('export', 'serializedQuery', 'columnMap', 'options', 'formats', 'records');
    }

    /**
     * @param  class-string<Exporter>  $exporter
     * @param  (Closure(Export, Builder<Model>, array<string, string>, array<string, mixed>, array<ExportFormat>, array<mixed> | null): bool) | null  $callback
     */
    public function assertDispatched(string $exporter, ?Closure $callback = null): static
    {
        foreach ($this->dispatched as $dispatch) {
            if ($dispatch['export']->exporter !== $exporter) {
                continue;
            }

            if ($callback && (! $callback(
                $dispatch['export'],
                QuerySerializer::unserialize($dispatch['serializedQuery']),
                $dispatch['columnMap'],
                $dispatch['options'],
                $dispatch['formats'],
                $dispatch['records'],
            ))) {
                continue;
            }

            Assert::assertTrue(true);

            return $this;
        }

        Assert::fail("The expected [{$exporter}] export was not dispatched.");
    }

    /**
     * @param  class-string<Exporter>  $exporter
     */
    public function assertDispatchedTimes(string $exporter, int $count = 1): static
    {
        $actualCount = count(array_filter(
            $this->dispatched,
            static fn (array $dispatch): bool => $dispatch['export']->exporter === $exporter,
        ));

        Assert::assertSame($count, $actualCount, "The expected [{$exporter}] export was dispatched {$actualCount} times instead of {$count} times.");

        return $this;
    }

    /**
     * @param  class-string<Exporter>  $exporter
     * @param  (Closure(Export, Builder<Model>, array<string, string>, array<string, mixed>, array<ExportFormat>, array<mixed> | null): bool) | null  $callback
     */
    public function assertNotDispatched(string $exporter, ?Closure $callback = null): static
    {
        foreach ($this->dispatched as $dispatch) {
            if ($dispatch['export']->exporter !== $exporter) {
                continue;
            }

            if ($callback && (! $callback(
                $dispatch['export'],
                QuerySerializer::unserialize($dispatch['serializedQuery']),
                $dispatch['columnMap'],
                $dispatch['options'],
                $dispatch['formats'],
                $dispatch['records'],
            ))) {
                continue;
            }

            Assert::fail("The unexpected [{$exporter}] export was dispatched" . ($callback ? ' with matching data.' : '.'));
        }

        Assert::assertTrue(true);

        return $this;
    }

    public function assertNothingDispatched(): static
    {
        Assert::assertEmpty($this->dispatched, 'Unexpected exports were dispatched.');

        return $this;
    }
}
