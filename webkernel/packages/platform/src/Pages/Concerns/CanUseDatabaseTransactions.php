<?php

namespace Webkernel\Platform\Pages\Concerns;

use Closure;
use Webkernel\Platform\Facades\Platform;
use \Webkernel\Database\Database as DB;

trait CanUseDatabaseTransactions
{
    protected ?bool $hasDatabaseTransactions = null;

    public function hasDatabaseTransactions(): bool
    {
        return $this->hasDatabaseTransactions ?? Platform::getCurrentOrDefaultPanel()->hasDatabaseTransactions();
    }

    protected function beginDatabaseTransaction(): void
    {
        if (! $this->hasDatabaseTransactions()) {
            return;
        }

        DB::beginTransaction();
    }

    protected function commitDatabaseTransaction(): void
    {
        if (! $this->hasDatabaseTransactions()) {
            return;
        }

        DB::commit();
    }

    protected function rollBackDatabaseTransaction(): void
    {
        if (! $this->hasDatabaseTransactions()) {
            return;
        }

        DB::rollBack();
    }

    protected function wrapInDatabaseTransaction(Closure $callback): mixed
    {
        if (! $this->hasDatabaseTransactions()) {
            return $callback();
        }

        /** @phpstan-ignore-next-line */
        return DB::transaction($callback);
    }
}
