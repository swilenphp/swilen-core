<?php

use Swilen\Events\Event;
use Swilen\Events\EventDispatcher;
use Swilen\Events\Listener;
use Swilen\Arthropod\Application;
use Swilen\Container\Container;

uses()->group('Events', 'Dispatcher');

beforeEach(function () {
    $this->dispatcher = new EventDispatcher();
});

it('registers a listener for an event', function () {
    $called = false;

    $this->dispatcher->listen('test.event', function () use (&$called) {
        $called = true;
    });

    expect($this->dispatcher->has('test.event'))->toBeTrue();

    $this->dispatcher->dispatch('test.event');

    expect($called)->toBeTrue();
});

it('dispatches event with payload', function () {
    $payload = null;

    $this->dispatcher->listen('user.created', function ($event) use (&$payload) {
        $payload = $event->payload();
    });

    $this->dispatcher->dispatch('user.created', ['name' => 'John', 'email' => 'john@example.com']);

    expect($payload)->toBe(['name' => 'John', 'email' => 'john@example.com']);
});

it('listeners receive Event object when event is string', function () {
    $eventReceived = null;

    $this->dispatcher->listen('order.placed', function ($event) use (&$eventReceived) {
        $eventReceived = $event;
    });

    $this->dispatcher->dispatch('order.placed', ['order_id' => 123]);

    expect($eventReceived)->toBeInstanceOf(Event::class);
    expect($eventReceived->name())->toBe('order.placed');
    expect($eventReceived->payload())->toBe(['order_id' => 123]);
});

it('listeners receive original Event object', function () {
    $eventReceived = null;

    $customEvent = new class('custom.event', ['data' => 'test']) implements Event {
        public function __construct(public string $eventName, public array $eventPayload) {}
        public function name(): string
        {
            return $this->eventName;
        }
        public function payload(): array
        {
            return $this->eventPayload;
        }
    };

    $this->dispatcher->listen('custom.event', function ($event) use (&$eventReceived) {
        $eventReceived = $event;
    });

    $this->dispatcher->dispatch($customEvent);

    expect($eventReceived)->toBe($customEvent);
});

it('listeners are called in priority order', function () {
    $order = [];

    $this->dispatcher->listen('priority.test', function () use (&$order) {
        $order[] = 'low';
    }, 100);

    $this->dispatcher->listen('priority.test', function () use (&$order) {
        $order[] = 'high';
    }, 1);

    $this->dispatcher->listen('priority.test', function () use (&$order) {
        $order[] = 'default';
    }, 10);

    $this->dispatcher->dispatch('priority.test');

    expect($order)->toBe(['high', 'default', 'low']);
});

it('tracks fired event count', function () {
    expect($this->dispatcher->firedCount('test.event'))->toBe(0);

    $this->dispatcher->dispatch('test.event');
    expect($this->dispatcher->firedCount('test.event'))->toBe(1);

    $this->dispatcher->dispatch('test.event');
    expect($this->dispatcher->firedCount('test.event'))->toBe(2);

    $this->dispatcher->dispatch('other.event');
    expect($this->dispatcher->firedCount('test.event'))->toBe(2);
});

it('removes specific listener', function () {
    $called1 = false;
    $called2 = false;

    $callback1 = function () use (&$called1) {
        $called1 = true;
    };
    $callback2 = function () use (&$called2) {
        $called2 = true;
    };

    $this->dispatcher->listen('remove.test', $callback1, 10);
    $this->dispatcher->listen('remove.test', $callback2, 10);

    $this->dispatcher->dispatch('remove.test');
    expect($called1)->toBeTrue();
    expect($called2)->toBeTrue();

    $called1 = false;
    $called2 = false;

    $this->dispatcher->forget('remove.test', $callback1, 10);

    $this->dispatcher->dispatch('remove.test');
    expect($called1)->toBeFalse();
    expect($called2)->toBeTrue();
});

it('does nothing when dispatching event with no listeners', function () {
    $this->dispatcher->dispatch('nonexistent.event');
    expect(true)->toBeTrue();
});

it('handles Listener interface implementation', function () {
    $handled = false;

    $listener = new class($handled) implements Listener {
        public function __construct(public bool &$handled) {}
        public function handle(Event $event): void
        {
            $this->handled = true;
        }
    };

    $this->dispatcher->listen('listener.event', $listener);
    $this->dispatcher->dispatch('listener.event');

    expect($listener->handled)->toBeTrue();
});

it('works with application container', function () {
    $app = new Application(dirname(__DIR__));
    Container::setInstance($app);

    $app->singleton(EventDispatcher::class, fn() => new EventDispatcher());

    $called = false;
    dispatcher()->listen('app.event', function () use (&$called) {
        $called = true;
    });

    dispatcher()->dispatch('app.event');

    expect($called)->toBeTrue();

    Container::setInstance(null);
});
