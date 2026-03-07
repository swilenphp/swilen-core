# Swilen

Swilen is an experimental re-architecture of WordPress designed for the modern web.

It provides an **async, event-driven runtime** and a **headless-first architecture**, while maintaining compatibility with existing WordPress plugins through a legacy compatibility layer.

The goal of Swilen is to modernize the WordPress ecosystem without forcing developers, designers, or content creators to abandon the tools they already use.

---

## Vision

WordPress powers a huge portion of the web, but its architecture was designed for a synchronous PHP environment from the early 2000s.

Swilen explores what WordPress could look like if it were built today:

- Async runtime
- Event-driven architecture
- Headless by default
- Modern plugin API
- Backwards compatibility with existing plugins

---

## Core Principles

### 1. Headless First

Swilen behaves as a headless CMS by default.

Themes are only loaded if they are detected.

```

Request → API → Content Service → JSON Response

```

Traditional theme rendering remains optional for compatibility.

---

### 2. Async Runtime

Swilen runs on an async server runtime (such as Swoole), enabling:

- persistent workers
- connection pooling
- async I/O
- faster request handling

---

### 3. Event-Driven Core

The core is built around an event bus instead of procedural hooks.

Example:

```php
Event::listen(PostPublished::class, function ($post) {
    SearchIndexer::index($post);
});
```

---

### 4. Legacy Compatibility Layer

Existing WordPress plugins can continue to work using a compatibility adapter.

Swilen implements the classic WordPress plugin contract:

- `add_action`
- `add_filter`
- global APIs
- common WordPress classes

This allows many existing plugins to run without modification.

---

### 5. Modern Plugin API

New plugins can use a modern architecture based on events, services, and dependency injection.

Example:

```php
class SeoPlugin implements Plugin
{
    public function boot(App $app)
    {
        $app->events->listen(PostPublished::class, function ($post) {
            Sitemap::update($post);
        });
    }
}
```

---

## Architecture Overview

```
HTTP Server (Async Runtime)
        │
        ▼
Swilen Core
        │
 ┌───────────────┬───────────────┐
 │ Modern Plugin │ Legacy Plugin │
 │ API (Events)  │ API (Hooks)   │
 └───────────────┴───────────────┘
        │
        ▼
Content / Users / Media Services
```

## Goals

- Improve performance and scalability
- Enable async workloads
- Provide a modern developer experience
- Preserve compatibility with the WordPress ecosystem

## Status

Swilen is currently an experimental project exploring a modern architecture for WordPress-like systems.

The API and internal design may change frequently.

## Inspiration

Swilen is inspired by modern headless CMS platforms and async runtimes while remaining compatible with the WordPress ecosystem.

## License

MIT
