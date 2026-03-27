<?php

namespace Swilen\Arthropod\Bootstrap;

use Swilen\Arthropod\Contract\BootstrappableService;
use Swilen\Shared\Arthropod\Application;

class RegisterProviders implements BootstrappableService
{
    /**
     * Register application service providers.
     *
     * @param \Swilen\Shared\Arthropod\Application $app
     *
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $app->registerProviders();
    }
}
