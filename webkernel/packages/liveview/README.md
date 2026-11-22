# Webkernel Liveview

`webkernel/liveview` provides server-rendered reactive components for Webkernel applications. The PHP runtime lives under `Webkernel\Liveview\`; browser assets and their build are owned by this package.

## Browser protocol

The current client protocol uses `wire:*` attributes, the `window.Livewire` global and the `window.livewireScriptConfig` object. These identifiers remain stable while package PHP namespaces and views migrate.

## Build

From this directory, run:

```bash
npm ci
npm run build
```

The lockfile pins all dependencies. The build writes classic, CSP-safe and ES module bundles plus source maps and a deterministic asset manifest to `dist/`. Build outputs must be regenerated from source; do not edit them by hand.

## Package integration

The Composer manifest declares the native service provider, configuration, view directives and public assets. The application resolves its package metadata through the generated Webkernel dependency manifest. Runtime regression coverage belongs to the Webkernel application suite; this package has no standalone PHPUnit or browser-test suite.

Upstream notice text is retained in `NOTICE-UPSTREAM-LIVEWIRE.md` while package metadata remains `proprietary`.
