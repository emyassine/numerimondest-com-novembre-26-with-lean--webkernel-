<?php

new class extends Webkernel\Liveview\Component {
    //
};
?>

<div>
    <div dusk="target">waiting</div>
</div>

<script>
    this.$el.querySelector('[dusk="target"]').textContent = 'js-loaded';
</script>
