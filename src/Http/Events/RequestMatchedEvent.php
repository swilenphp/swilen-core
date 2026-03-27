<?php

namespace Swilen\Http\Events;

use Swilen\Http\Request;
use Swilen\Routing\Route;

final class RequestMatchedEvent
{
    public function __construct(
        public readonly Request $request,
        public readonly Route $route
    ) {
    }
}
