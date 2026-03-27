<?php

namespace Swilen\Http\Events;

use Swilen\Http\Response;

final class ResponseSendingEvent
{
    public function __construct(
        public readonly Response $response
    ) {
    }
}
