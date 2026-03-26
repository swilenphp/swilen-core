<?php

namespace Swilen\Arthropod\Contract;

use Swilen\Arthropod\Application;
use Swilen\Http\Request;
use Swilen\Http\Response;

interface RuntimeContract
{
    public function bootstrap(): void;

    public function run(HttpKernel $kernel): void;
}
