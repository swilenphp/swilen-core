<?php

namespace Swilen\Http\Events;

use Swilen\Http\Request;

final class ControllerDispatchEvent
{
    public function __construct(
        public readonly Request $request,
        public readonly mixed $controller
    ) {
    }
}
