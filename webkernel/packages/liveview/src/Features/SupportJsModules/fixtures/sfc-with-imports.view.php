<?php

new class extends Webkernel\Liveview\Component {
    //
};
?>

<div>
    <div dusk="target">waiting</div>
</div>

<script>
import { greeting } from '/test-module.js'

this.$el.querySelector('[dusk="target"]').textContent = greeting
</script>
