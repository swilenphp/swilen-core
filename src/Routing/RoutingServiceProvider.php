<?php

namespace Swilen\Routing;

use Swilen\Events\EventDispatcher;
use Swilen\Http\Contract\ResponseContract;
use Swilen\Http\Response;
use Swilen\Petiole\ServiceProvider;
use Swilen\Routing\Contract\ResponseFactory;
use Swilen\Routing\ResponseFactory as RoutingResponseFactory;

class RoutingServiceProvider extends ServiceProvider
{
    /**
     * Register routing base services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRouter();

        $this->registerResponse();
    }

    /**
     * Register Router Manager.
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app->singleton('router', function ($app) {
            return new Router($app->make(EventDispatcher::class), $app);
        });
    }

    /**
     * Register Response.
     *
     * @return void
     */
    protected function registerResponse()
    {
        $this->app->bind('response', function () {
            return new Response();
        });

        $this->app->bind(ResponseContract::class, function () {
            return new Response();
        });

        $this->app->bind(ResponseFactory::class, function () {
            return new RoutingResponseFactory();
        });
    }
}
