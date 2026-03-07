# Especificación Técnica: WarpPress Core (WP-Async)

## 1. Visión General

**WarpPress Core** es una reimplementación **async y persistente** del núcleo de WordPress para entornos **Swoole**, diseñada como **WordPress más limpio, rápido y escalable**.  

**Objetivo principal**: Desacoplar subsistemas críticos del sistema de archivos y variables globales volátiles, permitiendo:
- Rendimiento empresarial (1000+ req/s por worker).  
- Compatibilidad con plugins heredados.  
- Soporte completo para **WordPress REST API v2** (headless).  
- Escalabilidad horizontal sin pérdida de estado.

**Stack técnico**:
- **Runtime**: Swoole 5.x+ (coroutines, tables, timers).  
- **PHP**: 8.4+.  
- **DB**: MySQL/PostgreSQL con connection pooling.  
- **Cache**: Redis + Swoole Tables.  
- **API**: WordPress REST API (wp-json/wp/v2) + extensiones custom. [pb4host](https://www.pb4host.com/wordpress-rest-api-development-in-2026-complete-guide-to-building-apis/)

***

## 2. Arquitectura de Alto Nivel

```
Boot Phase (Master Process)
├── Indexar plugins activos
├── Compilar rutas (FastRoute/Trie)
├── Pre-cargar opciones + roles
└── Inicializar Swoole Tables (cache L2)

Request Phase (Worker Coroutine)
├── Crear contexto limpio (L1 cache, $wp_query local)
├── Routing en memoria
├── Ejecutar hooks → plugins
├── DB async (yield en queries)
└── Response + cleanup

Task Workers (Background)
├── Cron jobs (Swoole Timer::tick)
├── Image processing
└── Email queues
```

***

## 3. Subsistemas Críticos

### 3.1 Motor de Reescritura (WP_Rewrite → WarpRoute)

**Problema heredado**: Regex secuenciales pesadas.  
**Solución**:
- **Pre‑compilación**: `rewrite_rules()` → TrieRouter/FastRoute en boot.  
- **Hot reload**: `add_rewrite_rule()` → evento broadcast a workers.  
- **url_to_postid()**: Lookup en memoria compartida (Swoole Table).  

**API pública**:
```php
WarpRoute::match($uri); // O(1) lookup
WarpRoute::reload_rules(); // Broadcast a workers
```

### 3.2 Caché de Objetos (WP_Object_Cache → WarpCache)

**Arquitectura dual**:
- **L1**: Array local por coroutine (volátil).  
- **L2**: Swoole Table + Redis (persistente entre workers).  

**Métodos clave**:
```php
wp_cache_add($key, $data, $group = 'default'); // L1 + L2 si TTL >0
wp_cache_get($key, $group); // L1 → L2 → DB
wp_cache_flush(); // Atómica: L1 + L2
```

### 3.3 Assets y Sistema de Archivos (WP_Scripts/Styles → WarpAssets)

**Virtual File Map**:
- Boot: Indexar `wp-content/plugins/themes` → memoria.  
- `wp_enqueue_script()`: Check en memoria, no `file_exists()`.  

**Beneficio**: 0 I/O bloqueante.

### 3.4 Base de Datos (WPDB → WarpDB Pool)

**Connection Pool** (Swoole Pool):
```php
$wpdb = WarpDB::acquire(); // Async yield
$results = $wpdb->get_results("SELECT * FROM posts"); // Non-blocking
WarpDB::release($wpdb); // Back to pool
```

**Aislamiento**: `$wpdb->last_error`, `$insert_id` locales por coroutine.

### 3.5 Cron (WP_Cron → WarpScheduler)

- **Swoole Timer::tick()** en Task Worker.  
- Eliminar `spawn_cron()` de hooks de carga.  

### 3.6 Buffering (OB → WarpBuffer)

- Buffer por coroutine ID.  
- `ob_start()` vinculado estrictamente a request lifecycle.

***

## 4. Matriz de Globales "Warpificadas"

| Variable Global | Impacto | Método WarpPress |
|-----------------|---------|------------------|
| `$wp_rewrite` | Alto | TrieRouter en memoria compartida |
| `$wp_object_cache` | Crítico | Dual L1/L2 (Coroutine + Table/Redis) |
| `$wp_query` | Crítico | Nueva instancia por coroutine |
| `$wpdb` | Crítico | Connection Pool con yield |
| `$wp_scripts/$wp_styles` | Medio | Contexto local por request |
| `$wp_roles` | Bajo | Swoole Table (boot-time) |

***

## 5. Compatibilidad con WordPress REST API

**Full compliance** con **WP REST API v2** (`/wp-json/wp/v2/*`): [developer.wordpress](https://developer.wordpress.org/rest-api/)

- **Endpoints core**: Posts, pages, users, comments, media (GET/POST/PUT/DELETE).  
- **Autenticación**: JWT, OAuth2, Application Passwords.  
- **Extensión custom**: `register_rest_route('warppress/v1', '/async-status')`.  
- **Async handling**: Queries DB non‑blocking, responses JSON estándar.  
- **Headless ready**: Soporte para React/Vue/Svelte frontends.

**Ejemplo endpoint**:
```php
register_rest_route('warppress/v1', '/cache-stats', [
    'methods' => 'GET',
    'callback' => 'warp_cache_stats_handler',
    'permission_callback' => 'current_user_can_read'
]);
```

***

## 6. Flujos de Vida

### 6.1 Boot Phase
1. Cargar `wp-config.php` → Swoole Tables.  
2. Indexar plugins → Virtual File Map.  
3. Compilar rutas → FastRoute.  
4. Task Workers spawn.

### 6.2 Request Phase
```
OnRequest:
├── WarpRoute::match($uri) → controller
├── New Coroutine Context (L1 cache, local globals)
├── Hooks: init → plugins → template_redirect
├── DB async + cache L2
└── Response (JSON/HTML) + cleanup L1
```

### 6.3 Cleanup
- Cache L1 flush.  
- DB connections release.  
- Buffer destroy.

***

## 7. Métricas de Rendimiento Esperadas

| Métrica | WordPress FPM | WarpPress Swoole |
|---------|---------------|------------------|
| Req/s por core | 50-100 | 800-1500 |
| Latencia media | 200-500ms | 10-50ms |
| Memoria peak | 50-100MB/req | 20-30MB/worker |
| CPU usage | 100% por req | 10-20% por req |

***

## 8. Requisitos de Implementación

- **PHP 8.4+**, Swoole 5.x, Redis 7.x.  
- **Hosting**: VPS+ (DigitalOcean, AWS Lightsail).  
- **Plugins compatibles**: 90% heredados (test suite requerida).  
- **Migración**: Script `wp warp-migrate` (copiar DB + reindex).

**Próximo paso**: Prototipo del WP_Rewrite → WarpRoute + benchmarks.
