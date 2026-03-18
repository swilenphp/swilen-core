<?php

namespace Swilen\Cache;

use Swilen\Shared\Support\Option;

class Cache
{
    private int $capacity;

    /** @var array<string, Node> */
    private array $items = [];

    private ?Node $head = null;
    private ?Node $tail = null;

    public function __construct(int $capacity = 1024)
    {
        $this->capacity = $capacity;
    }

    /**
     * @return Option<mixed>
     */
    public function get(string $key): Option
    {
        if (!isset($this->items[$key])) {
            return Option::empty();
        }

        $node = $this->items[$key];

        if ($node->expired()) {
            $this->delete($key);
            return Option::empty();
        }

        $this->moveToFront($node);

        return Option::of($node->value);
    }

    /**
     * @return mixed
     */
    public function getOrSet(string $key, callable $fn): mixed
    {
        $existing = $this->get($key);

        if ($existing->isPresent()) {
            return $existing->get();
        }

        [$value, $ttl] = $fn();

        $this->set($key, $value, $ttl);

        return $value;
    }

    public function set(string $key, mixed $value, int $ttl): void
    {
        $expiration = $ttl > 0 ? time() + $ttl : 0;

        if (isset($this->items[$key])) {
            $node = $this->items[$key];
            $node->value = $value;
            $node->expiration = $expiration;

            $this->moveToFront($node);
            return;
        }

        $node = new Node($key, $value, $expiration);

        $this->items[$key] = $node;
        $this->addToFront($node);

        if (count($this->items) > $this->capacity) {
            $this->evict();
        }
    }

    public function delete(string $key): void
    {
        if (!isset($this->items[$key])) {
            return;
        }

        $node = $this->items[$key];

        $this->removeNode($node);

        unset($this->items[$key]);
    }

    public function flush(): void
    {
        $this->items = [];
        $this->head = null;
        $this->tail = null;
    }

    private function evict(): void
    {
        if ($this->tail === null) {
            return;
        }

        $old = $this->tail;

        $this->removeNode($old);

        unset($this->items[$old->key]);
    }

    private function moveToFront(Node $node): void
    {
        $this->removeNode($node);
        $this->addToFront($node);
    }

    private function addToFront(Node $node): void
    {
        $node->prev = null;
        $node->next = $this->head;

        if ($this->head !== null) {
            $this->head->prev = $node;
        }

        $this->head = $node;

        if ($this->tail === null) {
            $this->tail = $node;
        }
    }

    private function removeNode(Node $node): void
    {
        if ($node->prev !== null) {
            $node->prev->next = $node->next;
        } else {
            $this->head = $node->next;
        }

        if ($node->next !== null) {
            $node->next->prev = $node->prev;
        } else {
            $this->tail = $node->prev;
        }

        $node->prev = null;
        $node->next = null;
    }
}
