<?php

namespace Swilen\Http\Events;

use Swilen\Http\Request;

final class MiddlewareStartEvent
{
    public function __construct(
        public readonly Request $request
    ) {
    }
}
