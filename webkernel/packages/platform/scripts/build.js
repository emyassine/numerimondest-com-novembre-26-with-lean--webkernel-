import { build } from 'esbuild'
import { spawnSync } from 'node:child_process'
import { mkdir, stat } from 'node:fs/promises'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const packageRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')

for (const [output, entry] of Object.entries({
    'dist/index.js': 'resources/js/index.js',
    'dist/echo.js': 'resources/js/echo.js',
})) {
    const outputPath = path.join(packageRoot, output)
    await mkdir(path.dirname(outputPath), { recursive: true })
    await build({
        absWorkingDir: packageRoot,
        entryPoints: [path.join(packageRoot, entry)],
        outfile: outputPath,
        bundle: true,
        minify: true,
        platform: 'browser',
        target: 'es2020',
        mainFields: ['module', 'main'],
        treeShaking: true,
        sourcemap: false,
        define: { 'process.env.NODE_ENV': '"production"' },
    })
    const outputInfo = await stat(outputPath)
    if (!outputInfo.isFile() || outputInfo.size === 0) {
        throw new Error(`The panel asset build produced no output for ${output}.`)
    }
}

const cssResult = spawnSync(
    path.join(packageRoot, 'node_modules', '.bin', process.platform === 'win32' ? 'tailwindcss.cmd' : 'tailwindcss'),
    ['--input', 'resources/css/theme.css', '--output', 'dist/theme.css', '--minify'],
    { cwd: packageRoot, stdio: 'inherit', shell: process.platform === 'win32' },
)

if (cssResult.error) throw cssResult.error
if (cssResult.status !== 0) process.exit(cssResult.status ?? 1)

const themeInfo = await stat(path.join(packageRoot, 'dist/theme.css'))
if (!themeInfo.isFile() || themeInfo.size === 0) {
    throw new Error('The panel theme build produced no CSS output.')
}
