---
name: platform-development
description:
  Builds Platform application and admin panel interfaces. Use when creating,
  modifying, testing, or debugging Platform panels, resources, relation managers,
  pages, schemas, forms, tables, actions, infolists, widgets, imports, or exports.
---

# Platform Development

- Platform is a Laravel UI framework built on Livewire, Alpine.js, and Tailwind CSS. UIs are defined in PHP via fluent, chainable components. Follow existing conventions in this app.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://www.webkernelphp.com/docs.

## Artisan

- Always use Platform-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan list`.
- Inspect required options before running, and always pass `--no-interaction`.

## Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

### Conditional form field visibility

```php
use Webkernel\Platform\Forms\Components\Select;
use Webkernel\Platform\Forms\Components\TextInput;
use Webkernel\Platform\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),
```

Use `Set $set` inside `->afterStateUpdated()` on a `->live()` field to mutate another field reactively. Prefer `->live(onBlur: true)` on text inputs to avoid per-keystroke updates:

### Reactive field update

```php
use Webkernel\Platform\Schemas\Components\Utilities\Set;
use Webkernel\Utilities\Str;

TextInput::make('title')
    ->required()
    ->live(onBlur: true)
    ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
        'slug',
        Str::slug($state ?? ''),
    )),

TextInput::make('slug')
    ->required(),
```

Compose layout by nesting `Section` and `Grid`. Children span one column by default. Use `->columnSpan()` to span multiple columns or `->columnSpanFull()` to span the full width:

### `Section` and `Grid` layout

```php
use Webkernel\Platform\Schemas\Components\Grid;
use Webkernel\Platform\Schemas\Components\Section;

Section::make('Details')
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('first_name'),
            TextInput::make('last_name'),
            TextInput::make('bio')
                ->columnSpanFull(),
        ]),
    ]),
```

Use `Repeater` for inline `HasMany` management. `->relationship()` with no args binds to the relationship matching the field name:

### `Repeater` for `HasMany`

```php
use Webkernel\Platform\Forms\Components\Repeater;

Repeater::make('qualifications')
    ->relationship()
    ->schema([
        TextInput::make('institution')
            ->required(),
        TextInput::make('qualification')
            ->required(),
    ])
    ->columns(2),
```

Use `state()` with a `Closure` to compute derived column values:

### Computed table column value

```php
use Webkernel\Platform\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),
```

Use `SelectFilter` for enum or relationship filters, and `Filter` with a `->query()` closure for custom logic:

### Table filters

```php
use Webkernel\Platform\Tables\Filters\Filter;
use Webkernel\Platform\Tables\Filters\SelectFilter;
use Webkernel\ORM\Builder;

SelectFilter::make('status')
    ->options(UserStatus::class),

SelectFilter::make('author')
    ->relationship('author', 'name'),

Filter::make('verified')
    ->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),
```

Actions are buttons that encapsulate optional modal forms and behavior:

### Action with modal form

```php
use Webkernel\Platform\Actions\Action;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data)),
```

## Testing

Testing setup (requires `pestphp/pest-plugin-livewire` in `composer.json`):

- Always call `$this->actingAs(User::factory()->create())` before testing panel functionality.
- For edit pages, pass `['record' => $user->id]` and use `->call('save')` (not `->call('create')`). Edit pages do not redirect after saving by default, so only assert a redirect when one is configured or `getRedirectUrl()` is overridden.

### Table test

```php
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));
```

### Create resource test

```php
use function Pest\Laravel\assertDatabaseHas;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertHasNoFormErrors()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);
```

### Edit resource test

```php
livewire(EditUser::class, ['record' => $user->id])
    ->fillForm(['name' => 'Updated'])
    ->call('save')
    ->assertNotified()
    ->assertHasNoFormErrors();

assertDatabaseHas(User::class, [
    'id' => $user->id,
    'name' => 'Updated',
]);
```

### Testing validation

```php
livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();
```

Use `->callAction(DeleteAction::class)` for page actions, or `->callAction(TestAction::make('name')->table($record))` for table actions:

### Calling actions

```php
use Webkernel\Platform\Actions\Testing\TestAction;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();
```

## Correct namespaces

- Form fields (`TextInput`, `Select`, `Repeater`, etc.): `Webkernel\Platform\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Webkernel\Platform\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Webkernel\Platform\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Webkernel\Platform\Schemas\Components\Utilities\`
- Table columns (`TextColumn`, `IconColumn`, etc.): `Webkernel\Platform\Tables\Columns\`
- Table filters (`SelectFilter`, `Filter`, etc.): `Webkernel\Platform\Tables\Filters\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Webkernel\Platform\Actions\`. Never use `Webkernel\Platform\Tables\Actions\`, `Webkernel\Platform\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Webkernel\Platform\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

## Common mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, `Fieldset`, and `Repeater` do not span all columns by default.
- **Use `Select::make('author_id')->relationship('author', 'name')` for `BelongsTo` fields.** `BelongsToSelect` does not exist; use `Select::relationship()`.
- **`Repeater` uses `->schema()`, not `->fields()`.**
- **Never add `->dehydrated(false)` to fields that need to be saved.** It strips the value from form state before `->action()` or the save handler runs. Only use it for helper/UI-only fields.
- **Use correct property types when overriding `Page`, `Resource`, and `Widget` properties.** These properties have union types or modifiers that must be preserved:
  - `$navigationIcon`: `protected static string | BackedEnum | null` (not `?string`)
  - `$navigationGroup`: `protected static string | UnitEnum | null` (not `?string`)
  - `$view`: `protected string` (not `protected static string`) on `Page` and `Widget` classes
