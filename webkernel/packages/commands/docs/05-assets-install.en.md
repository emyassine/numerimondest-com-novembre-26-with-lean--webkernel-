# `assets:install`

Installs locked frontend dependencies for selected packages using the package manager configured by `javascript.manager`.

```sh
./platform assets:install <package|all>
./platform assets:install all
```

The default selection is `all`. Package names use their Composer names; shell-expanded package paths such as `webkernel/*` are also accepted. `all` selects the application and installed workspace packages that have a `package.json` with a `scripts.build` command.

Installation uses the package manager's lockfile mode (`npm ci`, `pnpm install --frozen-lockfile`, or `yarn install --immutable`). Dependency versions are not upgraded.
