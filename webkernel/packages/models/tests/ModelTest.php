<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Webkernel\Database\Database;
use Webkernel\Models\Model;

final class TestWidget extends Model
{
    protected string $table = 'widgets';
    protected array $fillable = ['name'];
}

final class TestTrashedWidget extends Model
{
    protected string $table = 'trashed_widgets';
    protected array $fillable = ['name'];
    protected ?string $deleted_at_column = 'deleted_at';
}

final class InvalidTrashedWidget extends Model
{
    protected string $table = 'trashed_widgets';
    protected array $fillable = ['name', 'deleted_at'];
    protected ?string $deleted_at_column = 'deleted_at';
}

final class ModelTest extends TestCase
{
    protected function setUp(): void
    {
        Database::flush();
        Database::configure([
            'default' => 'testing',
            'connections' => ['testing' => ['driver' => 'sqlite', 'database' => ':memory:']],
        ]);
        Database::connection()->exec('CREATE TABLE widgets (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL)');
        Database::connection()->exec(
            'CREATE TABLE trashed_widgets (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, deleted_at TEXT NULL)',
        );
    }

    public function testCreateFindUpdateAndDelete(): void
    {
        $created = TestWidget::create(['name' => 'bolt']);
        $found = TestWidget::find((int) $created->id);
        self::assertNotNull($found);
        self::assertSame('bolt', $found->name);

        $found->name = 'nut';
        self::assertTrue($found->save());
        self::assertSame('nut', TestWidget::find((int) $found->id)?->name);
        self::assertTrue($found->delete());
        self::assertNull(TestWidget::find((int) $found->id));
        self::assertSame([], TestWidget::all());
    }

    public function testMassAssignmentRejectsUndeclaredColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TestWidget::create(['admin' => true]);
    }

    public function testSoftDeletedModelsCanBeFoundAndRestored(): void
    {
        $created = TestTrashedWidget::create(['name' => 'archived']);
        $id = (int) $created->id;

        self::assertTrue($created->delete());
        self::assertTrue($created->isTrashed());
        self::assertNull(TestTrashedWidget::find($id));
        self::assertSame([], TestTrashedWidget::query()->get());
        self::assertSame([], TestTrashedWidget::all());
        self::assertCount(1, TestTrashedWidget::onlyTrashed());
        self::assertSame('archived', TestTrashedWidget::findWithTrashed($id)?->name);
        self::assertSame(1, count(TestTrashedWidget::allWithTrashed()));

        $trashed = TestTrashedWidget::findWithTrashed($id);
        self::assertNotNull($trashed);
        self::assertTrue($trashed->restore());
        self::assertFalse($trashed->isTrashed());
        self::assertSame('archived', TestTrashedWidget::find($id)?->name);
        self::assertFalse($trashed->restore());
    }

    public function testForceDeletePermanentlyRemovesSoftDeletedModels(): void
    {
        $created = TestTrashedWidget::create(['name' => 'temporary']);
        $id = (int) $created->id;
        self::assertTrue($created->delete());

        $trashed = TestTrashedWidget::findWithTrashed($id);
        self::assertNotNull($trashed);
        self::assertTrue($trashed->forceDelete());
        self::assertNull(TestTrashedWidget::findWithTrashed($id));
        self::assertFalse($trashed->forceDelete());
    }

    public function testSoftDeletionColumnCannotBeMassAssigned(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new InvalidTrashedWidget();
    }
}
