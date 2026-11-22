import { build } from 'esbuild'
import { readFile, mkdir, stat } from 'node:fs/promises'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const packageRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')
const manifest = JSON.parse(await readFile(path.join(packageRoot, 'package.json'), 'utf8'))
const entries = manifest.webkernelBuild?.entries

if (!Array.isArray(entries) || entries.length === 0) {
    throw new Error('Declare at least one browser entry in webkernelBuild.entries.')
}

const outputRoot = path.join(packageRoot, 'dist')
await mkdir(outputRoot, { recursive: true })

await build({
    absWorkingDir: packageRoot,
    nodePaths: [path.join(packageRoot, 'node_modules')],
    entryPoints: entries.map((entry) => path.join(packageRoot, entry)),
    outbase: path.join(packageRoot, 'resources/js'),
    outdir: outputRoot,
    entryNames: '[dir]/[name]',
    bundle: true,
    minify: true,
    platform: 'browser',
    target: 'es2020',
    mainFields: ['module', 'main'],
    treeShaking: true,
    sourcemap: false,
    define: { 'process.env.NODE_ENV': '"production"' },
    plugins: [{
        name: 'external-style-sources',
        setup(context) {
            context.onResolve({ filter: /\.css$/ }, (args) => ({
                path: args.path,
                namespace: 'platform-style-source',
            }))
            context.onLoad({ filter: /.*/, namespace: 'platform-style-source' }, () => ({
                contents: '',
                loader: 'js',
            }))
        },
    }],
})

for (const entry of entries) {
    const output = path.join(outputRoot, entry.slice('resources/js/'.length))
    const info = await stat(output)
    if (!info.isFile() || info.size === 0) {
        throw new Error(`The build produced no output for ${entry}.`)
    }
}
