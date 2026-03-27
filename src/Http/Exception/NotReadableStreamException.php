<?php

namespace Swilen\Http\Exception;

use Throwable;

class NotReadableStreamException extends \RuntimeException
{
    public function __construct($message = 'Stream is not readable.', $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
