# Swilen

Swilen is a lightweight, high-performance backend framework built on top of OpenSwoole.

It is designed for modern applications that require **async execution**, **scalability**, and a **modular architecture**, without unnecessary complexity.

> Build fast systems with a simple, powerful foundation.

## Vision

Most backend systems are still built around synchronous request lifecycles.

Swilen takes a different approach:

- async-first execution
- persistent runtime
- event-driven design
- composable architecture

> Less overhead. More control. Better performance.

## What is Swilen?

Swilen is a backend framework for building:

- APIs
- CMS platforms
- SaaS applications
- real-time systems
- distributed services

It provides the core building blocks you need — without forcing a specific pattern or use case.

## Core Features

### ⚡ Async Runtime (OpenSwoole)

Swilen runs on OpenSwoole, enabling:

- persistent workers (no cold start per request)
- async I/O
- connection reuse
- high concurrency

### 🧩 Dependency Injection (DI)

- service container
- automatic resolution
- modular design

### 🧠 Event Bus

Swilen is built around an event-driven core:

```php
Event::listen(UserRegistered::class, function ($user) {
    Mailer::sendWelcome($user);
});
```

### 🌐 Routing

- flexible routing system
- middleware support
- REST-first design

### 🔐 Security (JWT)

- built-in JWT authentication
- stateless API security
- easy integration with clients

### 🗄️ Database Layer

- database-agnostic
- query builder
- schema builder
- flexible drivers

### ⚡ Cache

- in-memory or external stores
- simple API
- performance-focused

### 🔄 Background Jobs & Cron

- async jobs
- scheduled tasks
- non-blocking execution

### 🔌 Modular Architecture

Swilen is designed to be extended:

- modules
- services

## Communication Layer

Swilen supports modern communication strategies:

- **gRPC** for high-performance service communication (in roadmap)

These enable:

- microservices architectures
- distributed systems
- efficient internal communication

## License

MIT
