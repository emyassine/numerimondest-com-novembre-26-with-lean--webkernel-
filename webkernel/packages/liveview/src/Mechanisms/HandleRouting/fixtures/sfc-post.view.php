<?php

new class extends Webkernel\Liveview\Component {
    public \Webkernel\Liveview\Mechanisms\HandleRouting\RoutingPost $post;
};
?>

<div>
    <span>Post: {{ $post->title }}</span>
</div>
