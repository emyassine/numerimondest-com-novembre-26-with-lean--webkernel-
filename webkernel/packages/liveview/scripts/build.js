const crypto = require('node:crypto')
const fs = require('node:fs')
const path = require('node:path')
const brotliSize = require('brotli-size')
const esbuild = require('esbuild')

const packageRoot = path.resolve(__dirname, '..')
const distPath = path.join(packageRoot, 'dist')
const watch = process.argv.includes('--watch')

const cspPlugin = {
    name: 'webkernel-liveview-csp',
    setup(build) {
        build.onResolve({ filter: /^alpinejs$/ }, () => ({
            path: require.resolve('@alpinejs/csp'),
        }))
    },
}

const aliases = {
    name: 'webkernel-liveview-aliases',
    setup(build) {
        build.onResolve({ filter: /^@\// }, args => {
            const basePath = path.resolve(packageRoot, 'js', args.path.slice(2))

            for (const candidate of [basePath, `${basePath}.js`, path.join(basePath, 'index.js')]) {
                if (fs.existsSync(candidate) && fs.statSync(candidate).isFile()) {
                    return { path: candidate }
                }
            }

            return { errors: [{ text: `Cannot resolve Webkernel Liveview import: ${args.path}` }] }
        })
    },
}

async function buildVariant(file, options = {}) {
    return esbuild.build({
        absWorkingDir: packageRoot,
        entryPoints: ['js/index.js'],
        outfile: path.join(distPath, file),
        bundle: true,
        platform: options.format === 'esm' ? 'node' : 'browser',
        ...(options.format ? { format: options.format } : {}),
        ...(options.minify ? { minify: true } : {}),
        ...(options.sourcemap ? { sourcemap: 'linked' } : {}),
        define: {
            CDN: 'true',
            IS_CSP_BUILD: options.csp ? 'true' : 'false',
            'process.env.NODE_ENV': JSON.stringify(watch ? 'development' : 'production'),
        },
        plugins: [...(options.csp ? [cspPlugin] : []), aliases],
        ...(watch ? { watch: true } : {}),
        logLevel: 'warning',
    })
}

async function main() {
    fs.mkdirSync(distPath, { recursive: true })

    const variants = [
        ['liveview.js', {}],
        ['liveview.csp.js', { csp: true }],
        ['liveview.esm.js', { format: 'esm', sourcemap: true }],
        ['liveview.csp.esm.js', { format: 'esm', sourcemap: true, csp: true }],
        ['liveview.min.js', { minify: true, sourcemap: true }],
        ['liveview.csp.min.js', { minify: true, sourcemap: true, csp: true }],
    ]

    await Promise.all(variants.map(([file, options]) => buildVariant(file, options)))

    if (!watch) {
        for (const [file] of variants) {
            const outputPath = path.join(distPath, file)
            const output = fs.readFileSync(outputPath, 'utf8')
            fs.writeFileSync(outputPath, output.replace(/[ \t]+(?=\r?$)/gm, ''))
        }

        const hash = crypto.createHash('sha256')
            .update(fs.readFileSync(path.join(distPath, 'liveview.min.js')))
            .digest('hex')
            .slice(0, 8)

        fs.writeFileSync(path.join(distPath, 'manifest.json'), `${JSON.stringify({ '/livewire.js': hash })}\n`)

        for (const file of ['liveview.min.js', 'liveview.csp.min.js']) {
            const size = brotliSize.sync(fs.readFileSync(path.join(distPath, file)))
            console.log(`Bundle size [dist/${file}]: ${size} Brotli bytes`)
        }
    }
}

main().catch(error => {
    console.error(error)
    process.exitCode = 1
})
