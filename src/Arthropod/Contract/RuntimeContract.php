<?php

namespace Swilen\Arthropod\Contract;

use Swilen\Shared\Container\Container;

interface RuntimeContract
{
    /**
     * Update the runtime server settings.
     *
     * @param array $settings
     *
     * @return void
     */
    public function updateSettings(array $settings): void;

    /**
     * Set the container instance for the runtime.
     *
     * @param \Swilen\Container\Container $container
     *
     * @return void
     */
    public function setContainer(Container $container): void;

    /**
     * Run the application with the given HTTP kernel.
     *
     * @param \Swilen\Arthropod\Contract\HttpKernel $kernel
     *
     * @return void
     */
    public function run(HttpKernel $kernel): void;

    /**
     * Shutdown the runtime server gracefully.
     *
     * @return void
     */
    public function shutdown(): void;
}
