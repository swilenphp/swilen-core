<?php

namespace Swilen\Http\Exception;

class UnableToSeekException extends \RuntimeException
{
    public function __construct(int $offset, int $whence)
    {
        parent::__construct("Unable to seek to position $offset (whence: $whence)");
    }
}
