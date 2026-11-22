# `assets:sync`

Installs locked frontend dependencies, builds selected packages, and refreshes Composer's compiled asset manifest.

```sh
./platform assets:sync <package|all>
./platform assets:sync all
```

The default selection is `all`. Package names use their Composer names; shell-expanded package paths such as `webkernel/*` are also accepted. `all` selects the application and installed workspace packages that have a `package.json` with a `scripts.build` command.

Each package's `package.json` build script controls its build. Vite emits files into that package's `dist/` directory according to its Vite configuration. Dependency installation uses lockfile mode and does not upgrade dependency versions.
