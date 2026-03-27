<?php

namespace Swilen\Arthropod\Bootstrap;

use Swilen\Arthropod\Contract\BootstrappableService;
use Swilen\Petiole\Facade;
use Swilen\Shared\Arthropod\Application;

class Facades implements BootstrappableService
{
    /**
     * Boostrap facade application with instances.
     *
     * @param \Swilen\Shared\Arthropod\Application $app
     *
     * @return void
     */
    public function bootstrap(Application $app)
    {
        Facade::flushFacadeInstances();

        Facade::setFacadeApplication($app);
    }
}
