# CSRF Protection

Provides session-backed CSRF tokens for server-rendered forms. `Csrf::field()` returns an explicitly safe hidden input. Request handlers call `Csrf::verify($token)` before processing a state-changing request. Tokens are scoped by a short form identifier.

The package starts a native PHP session only when a token is requested, with strict session IDs, HTTP-only cookies, and `SameSite=Lax`. An application that starts its own session can configure it before rendering the first form.
