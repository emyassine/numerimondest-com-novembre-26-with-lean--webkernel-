# Deployment preparation

Run deployment commands from the application root.

## `deploy` and `deploy:test`

`./platform deploy` is an alias for `./platform deploy:test`. It refreshes Composer declarations, builds the package-owned PHP runtime artifacts, atomically publishes declared public assets, and activates `platform-cache.lock`. It does not run a JavaScript package manager or modify any `node_modules` directory.

## `deploy:prod`

`./platform deploy:prod` performs these steps in order:

1. Runs the equivalent of `./platform assets:sync`, using the configured JavaScript package manager and each package's lockfile to install build dependencies and produce its frontend assets.
2. Builds the PHP runtime artifacts.
3. Removes development-only JavaScript dependencies while retaining each package's declared production dependencies.
4. Publishes the prepared public assets and activates `platform-cache.lock`.

The production dependency installation is restricted to Composer-installed packages inside the current workspace plus the application root. It skips packages without a `node_modules` directory and refuses to prune a package that lacks the configured lockfile. npm uses `prune --omit=dev`; pnpm reinstalls from its lockfile with `--prod`. Yarn's package-local production pruning is not supported yet, so `deploy:prod` stops before activation when Yarn is configured. The command does not touch PHP's `internal/dependencies` tree or global package-manager caches. Build failures happen before pruning; pruning failures prevent activation of the new runtime-artifact generation.

This command prepares the current application directory for production. It does not create an archive or a separate release directory. Run it on a production/build workspace where development Node dependencies can be removed after the assets have been built.
