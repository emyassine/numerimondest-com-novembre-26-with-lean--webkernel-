<?php

new class extends Webkernel\Liveview\Component {
    public int $count = 1;

    public function increment()
    {
        $this->count++;
    }
};
?>