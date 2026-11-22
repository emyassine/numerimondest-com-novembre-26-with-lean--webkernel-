<?php

namespace Webkernel\Liveview\Mechanisms\HandleRequests;

use \Webkernel\HttpFoundation\Response;

class StreamedResponse extends Response
{
    /**
     * Override to prevent header modification after streaming has started.
     */
    public function sendHeaders(?int $statusCode = null): static
    {
        return $this;
    }
}