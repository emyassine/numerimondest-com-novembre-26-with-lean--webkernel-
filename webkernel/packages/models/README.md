# Webkernel Models

`Model` maps an application-owned table to an active-record API. A model declares its table and writable attributes; records use the default configured SQL connection. Model identifiers are restricted to simple SQL names and query values use prepared parameters.

```php
final class Article extends Model
{
    protected string $table = 'articles';
    protected array $fillable = ['title', 'body'];
}
```

Use `Article::create([...])`, `Article::find($id)`, `Article::query()->where(...)->get()`, and instance `save()` / `delete()`. Schema and migration ownership stays with the application or package that owns the table.

Models can opt into soft deletion by declaring a nullable `deleted_at` column name. Migrations must create that nullable timestamp column. Normal `query()`, `find()`, and `all()` calls then omit trashed rows; `findWithTrashed()`, `allWithTrashed()`, and `onlyTrashed()` support explicit recovery flows. Call `restore()` to recover a model or `forceDelete()` to remove it permanently. Authorization and confirmation remain responsibilities of the owning resource action.

```php
final class Article extends Model
{
    protected string $table = 'articles';
    protected array $fillable = ['title', 'body'];
    protected ?string $deleted_at_column = 'deleted_at';
}
```
