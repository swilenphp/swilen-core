<?php

use Swilen\Container\Container;
use Swilen\Pipeline\FilterPipeline;
use Swilen\Pipeline\Pipeline;

uses()->group('Pipeline');

beforeEach(function () {
    $this->container = new Container();
    $this->pipeline = new Pipeline($this->container);
    $this->filterPipeline = new FilterPipeline($this->container);
});

afterEach(function () {
    unset($this->container, $this->pipeline, $this->filterPipeline);
});

it('send object through pipeline and return result', function () {
    $result = $this->pipeline
        ->from('test')
        ->through([])
        ->then(function ($payload) {
            return $payload . '-processed';
        });

    expect($result)->toBe('test-processed');
});

it('pass object through single pipe', function () {
    $result = $this->pipeline
        ->from('hello')
        ->through([function ($payload, $next) {
            return strtoupper($payload) . '-' . $next($payload);
        }])
        ->then(function ($payload) {
            return $payload . '-end';
        });

    expect($result)->toBe('HELLO-hello-end');
});

it('pass object through multiple closure pipes', function () {
    $result = $this->pipeline
        ->from(1)
        ->through([
            function ($payload, $next) {
                return $next($payload + 10);
            },
            function ($payload, $next) {
                return $next($payload * 2);
            },
        ])
        ->then(function ($payload) {
            return $payload;
        });

    expect($result)->toBe(22);
});

it('pass object through class-based pipe', function () {
    $this->container->bind(IncrementPipe::class);

    $result = $this->pipeline
        ->from(5)
        ->through([IncrementPipe::class])
        ->then(function ($payload) {
            return $payload;
        });

    expect($result)->toBe(15);
});

it('pass object through class-based pipe with constructor parameters', function () {
    $this->container->bind(AddPipe::class);

    $result = $this->pipeline
        ->from(10)
        ->through([AddPipe::class])
        ->then(function ($payload) {
            return $payload;
        });

    expect($result)->toBe(30);
});

it('pass object through pipe and handleCarry returns the carry', function () {
    $result = $this->pipeline
        ->from(100)
        ->through([function ($payload, $next) {
            return $next($payload);
        }])
        ->then(function ($payload) {
            return $payload * 2;
        });

    expect($result)->toBe(200);
});

it('pass object through callable pipe', function () {
    $callable = function ($payload, $next) {
        return $next($payload . '-callable');
    };

    $result = $this->pipeline
        ->from('start')
        ->through([$callable])
        ->then(function ($payload) {
            return $payload;
        });

    expect($result)->toBe('start-callable');
});

it('allow custom method on pipe', function () {
    $this->container->bind(CustomMethodPipe::class);

    $result = $this->pipeline
        ->from('hello')
        ->viaMethod('process')
        ->through([CustomMethodPipe::class])
        ->then(function ($payload) {
            return $payload;
        });

    expect($result)->toBe('hello-processed');
});

it('use through with variadic arguments', function () {
    $result = $this->pipeline
        ->from(1)
        ->through(
            function ($payload, $next) {
                return $next($payload + 1);
            },
            function ($payload, $next) {
                return $next($payload + 2);
            }
        )
        ->then(function ($payload) {
            return $payload;
        });

    expect($result)->toBe(4);
});

it('pass object through pipe and exception is thrown', function () {
    $pipeline = new Pipeline($this->container);

    $pipeline
        ->from('test')
        ->through([
            function ($payload, $next) {
                throw new \RuntimeException('Error in pipe');
            }
        ])
        ->then(function ($payload) {
            return $payload;
        });
})->throws(\RuntimeException::class, 'Error in pipe');

it('use prepending object with from method', function () {
    $data = ['key' => 'value'];

    $result = $this->pipeline
        ->from($data)
        ->through([])
        ->then(function ($payload) {
            return $payload['key'];
        });

    expect($result)->toBe('value');
});

it('add and apply filter with closure', function () {
    $this->filterPipeline->add('test.hook', function ($value) {
        return $value . '-modified';
    });

    $result = $this->filterPipeline->apply('test.hook', 'original');

    expect($result)->toBe('original-modified');
});

it('apply filters in priority order', function () {
    $this->filterPipeline->add('test.hook', function ($value) {
        return $value . '-third';
    }, 30);
    $this->filterPipeline->add('test.hook', function ($value) {
        return $value . '-first';
    }, 10);
    $this->filterPipeline->add('test.hook', function ($value) {
        return $value . '-second';
    }, 20);

    $result = $this->filterPipeline->apply('test.hook', 'start');

    expect($result)->toBe('start-first-second-third');
});

it('pass additional arguments to filter', function () {
    $this->filterPipeline->add('test.hook', function ($value, $arg1, $arg2) {
        return $value . '-' . $arg1 . '-' . $arg2;
    });

    $result = $this->filterPipeline->apply('test.hook', 'start', 'a', 'b');

    expect($result)->toBe('start-a-b');
});

it('apply filter with class string callback', function () {
    $this->container->bind(UppercaseFilter::class);

    $this->filterPipeline->add('test.hook', UppercaseFilter::class);

    $result = $this->filterPipeline->apply('test.hook', 'hello');

    expect($result)->toBe('HELLO');
});

it('apply filter with class method string callback', function () {
    $this->container->bind(TransformFilter::class);

    $this->filterPipeline->add('test.hook', TransformFilter::class . '@transform');

    $result = $this->filterPipeline->apply('test.hook', 'hello');

    expect($result)->toBe('HELLO');
});

it('apply filter with array callback', function () {
    $obj = new class () {
        public function modify($value)
        {
            return $value . '-obj';
        }
    };

    $this->filterPipeline->add('test.hook', [$obj, 'modify']);

    $result = $this->filterPipeline->apply('test.hook', 'start');

    expect($result)->toBe('start-obj');
});

it('forget all filters for a hook', function () {
    $this->filterPipeline->add('test.hook', function ($value) {
        return $value . '-modified';
    });
    $this->filterPipeline->add('test.hook', function ($value) {
        return $value . '-also';
    });

    $this->filterPipeline->forget('test.hook');

    $result = $this->filterPipeline->apply('test.hook', 'original');

    expect($result)->toBe('original');
});

it('forget specific callback from hook', function () {
    $callback = function ($value) {
        return $value . '-remove';
    };

    $this->filterPipeline->add('test.hook', function ($value) {
        return $value . '-keep';
    });
    $this->filterPipeline->add('test.hook', $callback, 10);

    $this->filterPipeline->forget('test.hook', $callback, 10);

    $result = $this->filterPipeline->apply('test.hook', 'start');

    expect($result)->toBe('start-keep');
});

it('track current filter hook during execution', function () {
    $this->filterPipeline->add('hook.one', function ($value) {
        expect($this->filterPipeline->current())->toBe('hook.one');
        return $value;
    });
    $this->filterPipeline->add('hook.two', function ($value) {
        expect($this->filterPipeline->current())->toBe('hook.two');
        return $value;
    });

    $this->filterPipeline->apply('hook.one', 'test');
    $this->filterPipeline->apply('hook.two', 'test');

    expect($this->filterPipeline->current())->toBe('');
});

it('track fired counts for each hook', function () {
    $this->filterPipeline->add('test.hook', function ($value) {
        return $value;
    });

    $this->filterPipeline->apply('test.hook', 'a');
    $this->filterPipeline->apply('test.hook', 'b');
    $this->filterPipeline->apply('test.hook', 'c');

    $result = $this->filterPipeline->apply('test.hook', 'd');

    expect($result)->toBe('d');
});

it('return original value when hook has no filters', function () {
    $result = $this->filterPipeline->apply('nonexistent.hook', 'original');

    expect($result)->toBe('original');
});

it('cache sorted filters for performance', function () {
    $callCount = 0;

    $this->filterPipeline->add('test.hook', function ($value) use (&$callCount) {
        $callCount++;
        return $value;
    }, 20);
    $this->filterPipeline->add('test.hook', function ($value) use (&$callCount) {
        $callCount++;
        return $value;
    }, 10);

    $this->filterPipeline->apply('test.hook', '1');
    $this->filterPipeline->apply('test.hook', '2');

    expectt($callCount)->toBe(4);
});

class IncrementPipe
{
    public function handle($payload, $next)
    {
        return $next($payload + 10);
    }
}

class AddPipe
{
    protected $amount;

    public function __construct(int $amount = 20)
    {
        $this->amount = $amount;
    }

    public function handle($payload, $next)
    {
        return $next($payload + $this->amount);
    }
}

class CustomMethodPipe
{
    public function process($payload, $next)
    {
        return $next($payload . '-processed');
    }
}

class UppercaseFilter
{
    public function handle($value)
    {
        return strtoupper($value);
    }
}

class TransformFilter
{
    public function transform($value)
    {
        return strtoupper($value);
    }
}
