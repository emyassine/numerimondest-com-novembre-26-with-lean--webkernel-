<?php

new class extends \Webkernel\Liveview\Component
{
    //
};
?>

<div>
    <button wire:click="$js.test" dusk="test">Test</button>
</div>

<script>
    $js('test', () => {
        window.test = 'through dollar js'
    })
</script>
