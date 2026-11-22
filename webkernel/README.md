# Webkernel source packages

`packages/` contains the imported Webkernel essentials, including the view engine, change watcher, console, commands, service provider, lifecycle, and router. The codebase bootstrap is also present for the initial application integration. Package sources retain their Composer manifests, documentation, and license files.

The application in `../_virtualassistant.asia` installs these packages with Composer and uses the normal lifecycle declaration build. Its application provider declares routes and views; no app-owned authentication provider or database-backed route is registered.

The current Composer graph still brings in platform, Liveview, account, session, ORM, and database packages through the original codebase and router dependency chain. Their actual request-path cost should be measured and trimmed separately before treating the 4 ms rendering objective as met.
