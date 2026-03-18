<?php

namespace Swilen\Http\Client;

use Swilen\Shared\Support\RunableBag;

class Interceptors
{
    /**
     * @var RunableBag
     */
    public readonly RunableBag $request;

    /**
     * @var RunableBag
     */
    public readonly RunableBag $response;

    public function __construct()
    {
        $this->request  = new RunableBag();
        $this->response = new RunableBag();
    }
}
