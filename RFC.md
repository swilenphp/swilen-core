# RFC: WarpPress Core (WP-Async)

**Status:** Draft
**Author:** WarpPress Project
**Target Platform:** PHP 8.4+, Swoole 5.x
**Last Updated:** March 2026

---

# 1. Abstract

WarpPress Core (WP-Async) is an asynchronous and persistent reimplementation of the WordPress core designed to operate on a **Swoole-based runtime environment**. The primary goal of this project is to provide a **high-performance, horizontally scalable, and modernized execution model** for WordPress while maintaining compatibility with the majority of the existing plugin ecosystem.

WarpPress replaces traditional **PHP-FPM request isolation** with a **persistent coroutine-based architecture**, enabling significantly higher throughput and lower latency while preserving WordPress APIs and developer ergonomics.

---

# 2. Motivation

Traditional WordPress deployments rely on a **stateless PHP-FPM execution model** where each HTTP request initializes the full WordPress stack, executes logic, and then destroys all runtime state.

This model introduces several limitations:

- High initialization overhead per request
- Blocking I/O operations
- Heavy reliance on global variables
- Filesystem checks during runtime
- Inefficient database connection handling

WarpPress addresses these limitations through:

- Persistent workers
- Coroutine-based concurrency
- In-memory routing
- Asynchronous database access
- Multi-layer caching

The result is a **modern WordPress runtime capable of enterprise-scale throughput**.

---

# 3. Goals

The WarpPress architecture is designed with the following goals:

### 3.1 Performance

Achieve **800–1500 requests per second per CPU core** using coroutine workers.

### 3.2 Compatibility

Maintain compatibility with **~90% of existing WordPress plugins and themes**.

### 3.3 API Support

Provide full support for **WordPress REST API v2**, enabling headless CMS architectures.

### 3.4 Horizontal Scalability

Allow state-safe scaling across multiple workers and nodes.

### 3.5 Minimal Developer Friction

Preserve standard WordPress APIs and plugin development patterns.

---

# 4. Terminology

| Term          | Definition                                       |
| ------------- | ------------------------------------------------ |
| Worker        | Persistent Swoole process handling HTTP requests |
| Coroutine     | Lightweight concurrent execution context         |
| L1 Cache      | Per-request coroutine memory cache               |
| L2 Cache      | Shared persistent cache (Swoole Table + Redis)   |
| Boot Phase    | Server startup initialization stage              |
| Request Phase | Execution lifecycle for each HTTP request        |

---

# 5. System Architecture

WarpPress runs on a **multi-process Swoole server** consisting of:

- **Master Process**
- **Worker Processes**
- **Task Workers**

## 5.1 Boot Phase

During server startup, the system performs the following initialization:

1. Load configuration (`wp-config.php`)
2. Initialize shared Swoole Tables
3. Index plugins and themes
4. Compile rewrite rules into a routing structure
5. Preload options and roles
6. Spawn background task workers

```
Boot Phase
├── Load configuration
├── Index plugins
├── Compile routes
├── Preload options
└── Initialize cache tables
```

---

# 6. Request Lifecycle

Each incoming HTTP request is handled by a **coroutine inside a persistent worker**.

### Execution Flow

```
OnRequest
├── Create coroutine context
├── Route resolution
├── Execute hooks
├── Run plugin logic
├── Async DB operations
└── Generate response
```

### Context Isolation

To ensure plugin compatibility, WarpPress recreates several WordPress globals per coroutine:

- `$wp_query`
- `$wp_scripts`
- `$wp_styles`
- `$wpdb` state variables

This guarantees **request isolation within a persistent runtime**.

---

# 7. Core Subsystems

## 7.1 Routing System (WarpRoute)

WarpPress replaces the traditional WordPress rewrite engine with an **in-memory compiled router**.

### Problems with WP_Rewrite

- Sequential regex matching
- Heavy CPU overhead
- Runtime rule parsing

### Solution

Rewrite rules are compiled during boot into a **Trie or FastRoute dispatcher**.

### API

```php
WarpRoute::match($uri);
WarpRoute::reload_rules();
```

### Complexity

Route resolution operates in **O(1)** time.

---

# 7.2 Object Cache (WarpCache)

WarpPress implements a **two-tier cache architecture**.

| Layer | Scope          | Storage              |
| ----- | -------------- | -------------------- |
| L1    | Coroutine      | PHP array            |
| L2    | Worker cluster | Swoole Table + Redis |

### Cache Resolution Flow

```
Request → L1 → L2 → Database
```

### Example

```php
wp_cache_get($key, $group);
wp_cache_add($key, $data);
```

---

# 7.3 Asset Management (WarpAssets)

WarpPress replaces filesystem checks with a **pre-indexed asset map**.

### Boot Phase

```
Scan:
wp-content/plugins
wp-content/themes
```

The resulting file map is stored in memory.

### Benefit

Eliminates blocking `file_exists()` calls during requests.

---

# 7.4 Database Layer (WarpDB)

WarpPress introduces a **connection pool managed by Swoole**.

### Design

- Shared pool of persistent DB connections
- Coroutine-safe acquisition
- Non-blocking query execution

### Example

```php
$wpdb = WarpDB::acquire();
$results = $wpdb->get_results("SELECT * FROM posts");
WarpDB::release($wpdb);
```

---

# 7.5 Cron Scheduler (WarpScheduler)

The legacy `WP_Cron` mechanism is replaced by a **true background scheduler**.

### Implementation

```
Swoole Timer::tick()
```

Executed inside **task workers**.

Benefits:

- Reliable scheduling
- No dependency on page loads

---

# 7.6 Output Buffering (WarpBuffer)

Output buffering is managed per coroutine.

```
Coroutine ID → Buffer Instance
```

Buffers are automatically destroyed after response completion.

---

# 8. Global State Virtualization

WarpPress isolates global variables that traditionally cause cross-request contamination.

| Global             | WarpPress Strategy     |
| ------------------ | ---------------------- |
| `$wp_rewrite`      | Shared TrieRouter      |
| `$wp_object_cache` | Dual L1/L2 cache       |
| `$wp_query`        | Per coroutine instance |
| `$wpdb`            | Connection pool        |
| `$wp_scripts`      | Request-local          |
| `$wp_roles`        | Boot-time Swoole Table |

---

# 9. REST API Compatibility

WarpPress provides **full compatibility with WordPress REST API v2**.

Supported endpoints include:

- Posts
- Pages
- Users
- Comments
- Media

### Supported Authentication

- JWT
- OAuth2
- Application Passwords

### Example Extension

```php
register_rest_route('warppress/v1', '/cache-stats', [
    'methods' => 'GET',
    'callback' => 'warp_cache_stats_handler'
]);
```

---

# 10. Performance Expectations

| Metric            | WordPress (PHP-FPM) | WarpPress       |
| ----------------- | ------------------- | --------------- |
| Requests/sec/core | 50–100              | 800–1500        |
| Average latency   | 200–500 ms          | 10–50 ms        |
| Memory usage      | 50–100 MB/request   | 20–30 MB/worker |
| CPU utilization   | High                | Moderate        |

---

# 11. Deployment Requirements

### Runtime

- PHP 8.4+
- Swoole 5.x
- Redis 7.x

### Infrastructure

- VPS or dedicated server
- Example providers:
    - DigitalOcean
    - AWS Lightsail
    - Hetzner

---

# 12. Migration Strategy

WarpPress provides a CLI migration utility:

```
wp warp-migrate
```

Migration steps:

1. Copy existing WordPress database
2. Index assets
3. Compile routing rules
4. Initialize WarpPress runtime

---

# 13. Security Considerations

Key security measures include:

- Coroutine context isolation
- Connection pool validation
- Cache invalidation consistency
- Secure REST authentication mechanisms

Further security audits are recommended before production deployment.

---

# 14. Future Work

Planned improvements include:

- Async filesystem abstraction
- Native plugin compatibility layer
- Distributed cache clustering
- Automatic plugin safety sandboxing
- Observability (metrics, tracing, profiling)

---

# 15. Conclusion

WarpPress introduces a **modern asynchronous execution model for WordPress**, enabling significant performance improvements while preserving compatibility with the existing ecosystem.

By combining **persistent workers, coroutine concurrency, and in-memory subsystems**, WarpPress transforms WordPress into a platform capable of supporting **high-scale, headless, and enterprise workloads**.
