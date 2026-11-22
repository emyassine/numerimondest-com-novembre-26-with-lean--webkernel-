# Webkernel Database

`DatabaseManager` creates named PDO connections from the application's `database` configuration. Connections are cached by name for the lifetime of the manager. SQLite paths are resolved from the Composer application root and remain inside that root; `:memory:` is available for isolated tests. MySQL and PostgreSQL are used only when their PDO drivers are installed.

```php
$pdo = $database_manager->connection();
$reporting = $database_manager->connection('reporting');
$analytics = $database_manager->clickhouse('clickhouse');
$events = $analytics->select('SELECT event_name, count() AS total FROM events GROUP BY event_name');
```

`connection()` returns PDO for SQLite, MySQL, and PostgreSQL. ClickHouse is accessed through its HTTP API with `clickhouse()` and is intentionally separate from PDO transactions, migrations, and the model query builder. ClickHouse `select()` uses `JSONEachRow`; `execute()` sends DDL or write statements. SQL is supplied by trusted application code.

`Database::table()` provides the small prepared query builder used by the active-record models. Applications that need additional specialized stores can add a focused client beside these SQL connections without treating every datastore as a relational PDO connection.

Run configured application migrations with `./platform migrate:run`; roll back the latest batch with `./platform migrate:rollback`. Create a readable migration skeleton with `./platform make:migration add_status_to_accounts`. A name in the form `create_<table>_table` generates a `Schema::create()` / `Schema::drop()` pair. Migrations return anonymous `Migration` instances and use Webkernel's fluent `Blueprint`; they do not run during HTTP requests. This initial builder supports IDs, strings, text, integers, booleans, timestamps, unique/primary keys, and cascading foreign IDs. It is intentionally smaller than Laravel's schema API.
