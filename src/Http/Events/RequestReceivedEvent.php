<?php

namespace Swilen\Http\Events;

use Swilen\Http\Request;

final class RequestReceivedEvent
{
    public function __construct(
        public readonly Request $request
    ) {
    }
}
