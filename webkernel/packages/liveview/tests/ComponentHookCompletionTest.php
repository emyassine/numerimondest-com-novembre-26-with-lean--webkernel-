<?php declare(strict_types=1);

namespace Webkernel\Liveview\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Webkernel\Liveview\ComponentHook;

final class ComponentHookCompletionTest extends TestCase
{
    public static function lifecycleCalls(): array
    {
        return [
            ['callUpdate', ['property', 'property.child', 'new value']],
            ['callCall', ['save', [], static function (): void {}, [], null]],
            ['callRender', ['view', []]],
            ['callRenderIsland', ['island', 'view', []]],
            ['callRenderPlaceholder', ['view', []]],
        ];
    }

    #[DataProvider('lifecycleCalls')]
    public function testMissingLifecycleDoesNotCreateACompletion(string $method, array $arguments): void
    {
        $hook = new class extends ComponentHook {};
        self::assertNull($hook->$method(...$arguments));
    }

    #[DataProvider('lifecycleCalls')]
    public function testImmediateEffectsRunOnceWithoutANoopCompletion(string $method, array $arguments): void
    {
        $hook = new CompletionHookProbe();
        self::assertNull($hook->$method(...$arguments));
        self::assertSame(1, $hook->calls);
    }

    #[DataProvider('lifecycleCalls')]
    public function testCompletionPreservesScopeAndReferenceIsolation(string $method, array $arguments): void
    {
        $hook = new CompletionHookProbe();
        $hook->completion = [$hook, 'completeProtected'];
        $finish = $hook->$method(...$arguments);
        self::assertSame(0, $hook->completions);
        $html = 'original';
        self::assertNull($finish($html));
        self::assertSame('original', $html);
        self::assertSame(1, $hook->completions);
    }

    public function testCompletionExceptionsPropagate(): void
    {
        $hook = new CompletionHookProbe();
        $hook->completion = static function (): void { throw new RuntimeException('completion failed'); };
        $finish = $hook->callRender();
        $this->expectExceptionMessage('completion failed');
        $finish();
    }

    public function testLifecycleAndCompletionRetainScalarCoercion(): void
    {
        $hook = new class extends ComponentHook {
            public int $id;
            public ?string $html = null;

            public function render(int $id): callable
            {
                $this->id = $id;

                return function (string $html): void { $this->html = $html; };
            }
        };

        $finish = $hook->callRender('7');
        $finish(42);
        self::assertSame(7, $hook->id);
        self::assertSame('42', $hook->html);
    }
}

final class CompletionHookProbe extends ComponentHook
{
    public int $calls = 0;
    public int $completions = 0;
    public mixed $completion = null;

    public function update(...$arguments): mixed { $this->calls++; return $this->completion; }
    public function call(...$arguments): mixed { $this->calls++; return $this->completion; }
    public function render(...$arguments): mixed { $this->calls++; return $this->completion; }
    public function renderIsland(...$arguments): mixed { $this->calls++; return $this->completion; }
    public function renderPlaceholder(...$arguments): mixed { $this->calls++; return $this->completion; }

    protected function completeProtected(string &$html): string
    {
        $this->completions++;
        $html = 'changed locally';
        return 'discarded';
    }
}
