# `assets:build`

Runs the `build` script for selected frontend packages and refreshes Composer's compiled asset manifest.

```sh
./platform assets:build <package|all>
./platform assets:build webkernel/components
./platform assets:build all
```

The default selection is `all`. Package names use their Composer names; shell-expanded package paths such as `webkernel/*` are also accepted. `all` selects the application and installed workspace packages that have a `package.json` with a `scripts.build` command.

Each package owns its `package.json`, build configuration, source files, and `dist/` output. Vite compiles configured entries and their imports. Tailwind scans view templates only when their paths are included with `@source`; scanning a template does not make it a JavaScript entry.

`ServiceProvider::ASSETS` declares package source entry points for Composer's asset manifest. `ServiceProvider::PUBLIC_ASSETS` declares directories that Webkernel may serve, including generated `dist/` files.
