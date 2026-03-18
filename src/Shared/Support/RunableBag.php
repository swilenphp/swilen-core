<?php

namespace Swilen\Shared\Support;

class RunableBag
{
    /**
     * The callbacks runables.
     *
     * @var array<callable>
     */
    protected array $runables = [];

    /**
     * Add given callback to runables.
     *
     * @param callable $callback
     */
    public function add(callable $callback)
    {
        $this->runables[] = $callback;
    }

    /**
     * Remove given callback from runables.
     *
     * @param callable $callback
     */
    public function eject(callable $callback)
    {
        $this->runables = array_filter($this->runables, function ($runable) use ($callback) {
            return $runable !== $callback;
        });
    }

    /**
     * Run callback with given data param.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function run($data)
    {
        foreach ($this->runables as $callback) {
            $data = $callback($data);
        }

        return $data;
    }

    /**
     * Run callback with given data param.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function __invoke($data)
    {
        return $this->run($data);
    }
}
