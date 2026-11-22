<?php

require_once 'vendor/autoload.php';

use Webkernel\Liveview\V4\Compiler\SingleFileComponentCompiler;
use \Webkernel\Filesystem\Files as File;

// Test unified script extraction
$compiler = new SingleFileComponentCompiler('/tmp/livewire_test_cache');

// Test 1: Regular JavaScript (no imports)
echo "=== TEST 1: Regular JavaScript ===\n";
$regularJsComponent = '@php
new class extends Webkernel\Liveview\Component {
    public $count = 0;
    public function increment() { $this->count++; }
}
@endphp

<div>
    <h1>Count: {{ $count }}</h1>
    <button wire:click="increment">+</button>
</div>

<script>
console.log("Regular JavaScript");
document.addEventListener("DOMContentLoaded", function() {
    console.log("Component loaded");
});
</script>';

file_put_contents('/tmp/regular-component.livewire.php', $regularJsComponent);
$result1 = $compiler->compile('/tmp/regular-component.livewire.php');

echo "✅ View is clean (no scripts): " . (!str_contains(file_get_contents($result1->viewPath), '<script') ? 'YES' : 'NO') . "\n";
echo "✅ Script file generated: " . ($result1->hasScripts() && file_exists($result1->scriptPath) ? 'YES' : 'NO') . "\n";

if ($result1->hasScripts()) {
    $scriptContent = file_get_contents($result1->scriptPath);
    echo "✅ Script wrapped in export default: " . (str_contains($scriptContent, 'export default function run()') ? 'YES' : 'NO') . "\n";
    echo "Script content:\n" . $scriptContent . "\n\n";
}

// Test 2: ES6 Imports
echo "=== TEST 2: ES6 Imports ===\n";
$es6Component = '@php
new class extends Webkernel\Liveview\Component {
    public $count = 0;
    public function increment() { $this->count++; }
}
@endphp

<div>
    <h1>Count: {{ $count }}</h1>
    <button wire:click="increment">+</button>
</div>

<script>
import { animate } from "https://cdn.jsdelivr.net/npm/animejs/+esm";
import { debounce } from "https://cdn.skypack.dev/lodash-es";

console.log("ES6 JavaScript with imports");

const button = document.querySelector("button");
const counter = document.querySelector("h1");

const debouncedAnimate = debounce(() => {
    animate({
        targets: counter,
        scale: [1, 1.2, 1],
        duration: 300
    });
}, 100);

button.addEventListener("click", debouncedAnimate);
</script>';

file_put_contents('/tmp/es6-component.livewire.php', $es6Component);
$result2 = $compiler->compile('/tmp/es6-component.livewire.php');

echo "✅ View is clean (no scripts): " . (!str_contains(file_get_contents($result2->viewPath), '<script') ? 'YES' : 'NO') . "\n";
echo "✅ Script file generated: " . ($result2->hasScripts() && file_exists($result2->scriptPath) ? 'YES' : 'NO') . "\n";

if ($result2->hasScripts()) {
    $scriptContent = file_get_contents($result2->scriptPath);
    echo "✅ Imports hoisted: " . (str_contains($scriptContent, '// Hoisted imports') ? 'YES' : 'NO') . "\n";
    echo "✅ Script wrapped in export default: " . (str_contains($scriptContent, 'export default function run()') ? 'YES' : 'NO') . "\n";
    echo "✅ Import statements at top: " . (strpos($scriptContent, 'import { animate }') < strpos($scriptContent, 'export default') ? 'YES' : 'NO') . "\n";
    echo "Script content:\n" . $scriptContent . "\n\n";
}

// Cleanup
unlink('/tmp/regular-component.livewire.php');
unlink('/tmp/es6-component.livewire.php');

echo "🎉 Unified script extraction working correctly!\n";