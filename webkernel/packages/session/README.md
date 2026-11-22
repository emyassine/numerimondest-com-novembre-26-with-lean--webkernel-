# Webkernel Sessions

`Webkernel\Session\Session` wraps PHP's native server-side sessions. Sessions start only when called, use the configured private runtime session directory, strict mode, HTTP-only cookies, and SameSite protection. `get`, `put`, `pull`, `forget`, `flash`, `regenerate`, `invalidate`, `destroy`, and `save` cover the common request/authentication lifecycle.

```php
Session::put('user_id', $user->id);
Session::regenerate();

if (Session::get('user_id') !== null) {
    // Resolve the authenticated user on the server.
}
```

Store stable identifiers and small values; authorization still resolves the current user and permissions on the server. `Csrf` uses this same session lifecycle.
