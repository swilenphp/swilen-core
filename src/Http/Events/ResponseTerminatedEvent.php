<?php

namespace Swilen\Http\Events;

use Swilen\Http\Request;
use Swilen\Http\Response;

final class ResponseTerminatedEvent
{
    public function __construct(
        public readonly Request $request,
        public readonly Response $response
    ) {
    }
}
