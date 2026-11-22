# Webkernel Liveview

Liveview provides server-rendered reactive components for Webkernel applications. It sends signed component state with browser updates and renders the resulting HTML on the server.

## Package structure

- `src/` contains PHP runtime, component features and package integration.
- `js/` contains the browser runtime and feature modules.
- `scripts/build.js` creates the classic, CSP-safe and ES module bundles.
- `dist/` contains the checked-in bundles, source maps and asset manifest.

## Browser build

Install only the locked package dependencies and build from this directory:

```bash
npm ci
npm run build
```

The build is deterministic. It resolves package-local `@/` imports and writes all outputs to `dist/`; the manifest identifier is derived from the minified bundle. Commit bundle changes with the source change that produced them. Do not edit generated bundles or source maps by hand.

## PHP validation

The package has no standalone PHPUnit or browser-test suite. Run framework regressions from the Webkernel application using its configured test command. Add focused tests there when changing a runtime contract.

## Source conventions

- Follow the existing JavaScript style in the affected module.
- Keep feature registration order explicit in `js/features/index.js` and `js/directives/index.js`.
- Keep browser protocol names stable until their server and Platform consumers are migrated together.
- Keep third-party notices and dependency ownership intact.
