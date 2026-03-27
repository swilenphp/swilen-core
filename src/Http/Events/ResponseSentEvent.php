<?php

namespace Swilen\Http\Events;

use Swilen\Http\Response;

final class ResponseSentEvent
{
    public function __construct(
        public readonly Response $response
    ) {
    }
}
