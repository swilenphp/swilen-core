<?php

namespace Swilen\Arthropod\Contract;

use Swilen\Http\Request;
use Swilen\Http\Response;

interface HttpKernel
{
    /**
     * Bootstrap the application and prepare it to handle requests.
     *
     * @return void
     */
    public function bootstrap(): void;

    /**
     * Handle the incoming request and send it to the router.
     *
     * @param \Swilen\Http\Request $request
     *
     * @return \Swilen\Http\Response
     */
    public function handle(Request $request): Response;
}
