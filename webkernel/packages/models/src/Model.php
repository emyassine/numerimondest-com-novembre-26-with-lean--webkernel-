<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\Models;

use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use RuntimeException;
use Webkernel\Database\Database;
use Webkernel\Database\Query;

/** One table row. Concrete models live beside the package that owns their table. */
abstract class Model
{
    protected string $table = '';
    protected string $primary_key = 'id';
    protected string $route_key = '';

    protected ?string $deleted_at_column = null;

    /** @var list<string> */
    protected array $fillable = [];

    /** @var array<string, mixed> */
    protected array $attributes = [];

    /** @var list<string> */
    protected array $hidden = [];

    private bool $exists = false;

    /** @param array<string, mixed> $attributes */
    public function __construct(array $attributes = [])
    {
        self::assertIdentifier($this->primary_key);
        if ($this->route_key !== '') { self::assertIdentifier($this->route_key); }
        if ($this->deleted_at_column !== null) {
            self::assertIdentifier($this->deleted_at_column);
            if (
                $this->deleted_at_column === $this->primary_key
                || in_array($this->deleted_at_column, $this->fillable, true)
            ) {
                throw new InvalidArgumentException('The soft deletion column must be a separate, non-fillable model attribute.');
            }
        }
        $this->fill($attributes);
    }

    public static function table(): string
    {
        return self::resolveTableName(new static());
    }

    public static function query(): Query
    {
        $model = new static();
        $query = Database::table(self::resolveTableName($model));
        if ($model->deleted_at_column !== null) {
            $query->where($model->deleted_at_column, null);
        }

        return $query;
    }

    private static function resolveTableName(self $model): string
    {
        if ($model->table !== '') {
            return $model->table;
        }

        $short_name = (new ReflectionClass($model))->getShortName();
        return strtolower((string) preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $short_name)).'s';
    }

    /** Hydrates a trusted database row, preserving hidden-field serialization and persistence state. */
    public static function fromRow(array $row): static
    {
        return (new static())->hydrate($row);
    }

    public static function find(int|string $id): ?static
    {
        $prototype = new static();
        $row = static::query()->where($prototype->primary_key, $id)->first();
        return $row === null ? null : $prototype->hydrate($row);
    }

    /** Resolve a route segment through the model's configured route key. */
    public static function resolveRouteBinding(string $value, ?string $field = null): ?static
    {
        $model = new static();
        $field ??= $model->route_key !== '' ? $model->route_key : $model->primary_key;
        self::assertIdentifier($field);
        $row = static::query()->where($field, $value)->first();

        return $row === null ? null : $model->hydrate($row);
    }

    public function getRouteKeyName(): string
    {
        return $this->route_key !== '' ? $this->route_key : $this->primary_key;
    }

    public function getKey(): int|string|null
    {
        $key = $this->attributes[$this->primary_key] ?? null;

        return is_int($key) || is_string($key) ? $key : null;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public static function findWithTrashed(int|string $id): ?static
    {
        $prototype = new static();
        self::softDeleteColumn($prototype);
        $row = Database::table(self::resolveTableName($prototype))
            ->where($prototype->primary_key, $id)
            ->first();

        return $row === null ? null : $prototype->hydrate($row);
    }

    /** @return list<static> */
    public static function all(): array
    {
        $models = [];
        foreach (static::query()->get() as $row) {
            $models[] = (new static())->hydrate($row);
        }
        return $models;
    }

    /** @return list<static> */
    public static function allWithTrashed(): array
    {
        $prototype = new static();
        self::softDeleteColumn($prototype);
        $models = [];
        foreach (Database::table(self::resolveTableName($prototype))->get() as $row) {
            $models[] = (new static())->hydrate($row);
        }

        return $models;
    }

    /** @return list<static> */
    public static function onlyTrashed(): array
    {
        $prototype = new static();
        $deleted_at_column = self::softDeleteColumn($prototype);

        $models = [];
        foreach (
            Database::table(self::resolveTableName($prototype))
                ->where($deleted_at_column, '!=', null)
                ->get() as $row
        ) {
            $models[] = (new static())->hydrate($row);
        }

        return $models;
    }

    /** @param array<string, mixed> $attributes */
    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    /** @param array<string, mixed> $attributes */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $column => $value) {
            if (! is_string($column) || ! in_array($column, $this->fillable, true)) {
                throw new InvalidArgumentException('The model attribute is not fillable.');
            }
            if (! is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException('Model attributes must be scalar or null.');
            }
            $this->attributes[$column] = $value;
        }
        return $this;
    }

    public function save(): bool
    {
        $key = $this->primary_key;
        if ($this->exists) {
            $values = $this->writableAttributes();
            unset($values[$key]);
            if ($values === []) {
                return false;
            }
            return static::query()->where($key, $this->attributes[$key] ?? null)->update($values) > 0;
        }

        $id = static::query()->insert($this->writableAttributes());
        if ($id > 0) {
            $this->attributes[$key] = $id;
        }
        $this->exists = true;
        return true;
    }

    public function delete(): bool
    {
        if (! $this->exists) {
            return false;
        }
        $query = static::query()->where($this->primary_key, $this->primaryKeyValue());
        if ($this->deleted_at_column !== null) {
            $deleted_at = gmdate('Y-m-d H:i:s');
            $deleted = $query->update([$this->deleted_at_column => $deleted_at]) > 0;
            if ($deleted) {
                $this->attributes[$this->deleted_at_column] = $deleted_at;
            }

            return $deleted;
        }

        $deleted = $query->delete() > 0;
        $this->exists = false;
        return $deleted;
    }

    public function restore(): bool
    {
        if ($this->deleted_at_column === null) {
            throw new LogicException(static::class.' does not enable soft deletion.');
        }
        if (! $this->exists || ($this->attributes[$this->deleted_at_column] ?? null) === null) {
            return false;
        }

        $restored = Database::table(self::resolveTableName($this))
            ->where($this->primary_key, $this->primaryKeyValue())
            ->where($this->deleted_at_column, '!=', null)
            ->update([$this->deleted_at_column => null]) > 0;
        if ($restored) {
            $this->attributes[$this->deleted_at_column] = null;
        }

        return $restored;
    }

    public function forceDelete(): bool
    {
        if (! $this->exists) {
            return false;
        }

        $deleted = Database::table(self::resolveTableName($this))
            ->where($this->primary_key, $this->primaryKeyValue())
            ->delete() > 0;
        if ($deleted) {
            $this->exists = false;
        }

        return $deleted;
    }

    public function isTrashed(): bool
    {
        return $this->deleted_at_column !== null
            && ($this->attributes[$this->deleted_at_column] ?? null) !== null;
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $attributes = $this->attributes;
        foreach ($this->hidden as $key) {
            unset($attributes[$key]);
        }
        return $attributes;
    }

    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    public function __set(string $name, mixed $value): void
    {
        if (! in_array($name, $this->fillable, true) || (! is_scalar($value) && $value !== null)) {
            throw new InvalidArgumentException('The model attribute is not writable.');
        }
        $this->attributes[$name] = $value;
    }

    /** @return array<string, mixed> */
    private function writableAttributes(): array
    {
        $attributes = [];
        foreach ($this->fillable as $column) {
            self::assertIdentifier($column);
            if (array_key_exists($column, $this->attributes)) {
                $attributes[$column] = $this->attributes[$column];
            }
        }
        if ($attributes === []) {
            throw new InvalidArgumentException('A model needs at least one fillable value before saving.');
        }
        return $attributes;
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): static
    {
        $allowed = [...$this->fillable, $this->primary_key];
        if ($this->route_key !== '') { $allowed[] = $this->route_key; }
        if ($this->deleted_at_column !== null) {
            $allowed[] = $this->deleted_at_column;
        }
        foreach ($row as $column => $value) {
            if (! is_string($column) || ! in_array($column, $allowed, true) || (! is_scalar($value) && $value !== null)) {
                throw new RuntimeException('The database returned an undeclared model attribute.');
            }
        }
        $this->attributes = $row;
        $this->exists = true;
        return $this;
    }

    private function primaryKeyValue(): int|string
    {
        $id = $this->attributes[$this->primary_key] ?? null;
        if ((! is_int($id) && ! is_string($id)) || $id === '') {
            throw new RuntimeException('The model must be persisted before this operation.');
        }
        return $id;
    }

    private static function assertIdentifier(string $identifier): void
    {
        if (preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/', $identifier) !== 1) {
            throw new InvalidArgumentException('Model identifiers must be simple SQL names.');
        }
    }

    private static function softDeleteColumn(self $model): string
    {
        if ($model->deleted_at_column === null) {
            throw new LogicException($model::class.' does not enable soft deletion.');
        }

        return $model->deleted_at_column;
    }
}
