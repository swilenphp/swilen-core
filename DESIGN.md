* **Core / Kernel**
* **Container (DI)**
* **EventBus**
* **Queue / Workers**
* **Router**
* **Rewrite Engine**
* **Plugin Loader**
* **Legacy Adapter (hooks WP)**

---

# Arquitectura del core de FluxPress

```text
fluxpress/
 ├ kernel/
 │   ├ Kernel.php
 │   ├ Container.php
 │   ├ EventBus.php
 │   ├ Queue.php
 │   ├ HttpRouter.php
 │   ├ RewriteEngine.php
 │   ├ PluginManager.php
 │   └ LegacyHooks.php (backward wordpress compatibility)
 │
 ├ plugins/
 ├ routes/
 └ public/
```

---

# 1. Container (Dependency Injection)

Esto permite que el core comparta servicios.

```php
<?php

class Container
{
    protected array $bindings = [];
    protected array $instances = [];

    public function bind(string $key, callable $resolver)
    {
        $this->bindings[$key] = $resolver;
    }

    public function singleton(string $key, callable $resolver)
    {
        $this->instances[$key] = $resolver($this);
    }

    public function make(string $key)
    {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        if (isset($this->bindings[$key])) {
            return $this->bindings[$key]($this);
        }

        throw new Exception("Service {$key} not found");
    }
}
```

---

# 2. Router avanzado (con parámetros)

```php
<?php

class Router
{
    protected array $routes = [];

    public function get(string $path, callable $handler)
    {
        $this->routes['GET'][] = [$path, $handler];
    }

    public function dispatch(string $method, string $uri)
    {
        $uri = trim(parse_url($uri, PHP_URL_PATH), '/');

        foreach ($this->routes[$method] ?? [] as [$path, $handler]) {

            $pattern = preg_replace('#\{(\w+)\}#', '([^/]+)', $path);
            $pattern = "#^" . trim($pattern, '/') . "$#";

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                return $handler(...$matches);
            }
        }

        http_response_code(404);
        echo "Not Found";
    }
}
```

Ejemplo:

```php
$router->get('post/{slug}', function($slug) {
    echo "Post: " . $slug;
});
```

---

# 3. Rewrite Engine (estilo wp_rewrite)

WordPress usa reglas regex. Podemos hacer algo similar.

```php
<?php

class RewriteEngine
{
    protected array $rules = [];

    public function addRule(string $pattern, string $target)
    {
        $this->rules[$pattern] = $target;
    }

    public function resolve(string $uri): string
    {
        foreach ($this->rules as $pattern => $target) {
            if (preg_match("#{$pattern}#", $uri, $matches)) {

                foreach ($matches as $key => $value) {
                    $target = str_replace('$'.$key, $value, $target);
                }

                return $target;
            }
        }

        return $uri;
    }
}
```

Ejemplo:

```php
$rewrite->addRule(
    '^post/([a-z0-9-]+)$',
    'index.php?post=$1'
);
```

---

# 4. Plugin Manager

Carga plugins automáticamente.

```php
<?php

class PluginManager
{
    protected string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function load(): void
    {
        foreach (glob($this->path . '/*/*.php') as $plugin) {
            require $plugin;
        }
    }
}
```

---

# 5. Legacy Hooks Adapter (WordPress compatibility)

Esto permite usar:

```php
add_action(...)
do_action(...)
```

```php
<?php

use FluxPress\Kernel

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10)
    {
        kernel()->events->subscribe("wp:".$hook, $callback, $priority);
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args)
    {
        kernel()->events->dispatch("wp:".$hook, ...$args);
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10)
    {
        kernel()->pipes->add("wp:".$hook, $callback, $priority);
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args)
    {
        return kernel()->pipes->run("wp:".$hook, $value, ...$args);
    }
}

```

Uso en plugin legacy:

```php
add_action('init', function() {
    echo "Plugin loaded";
});
```

---

# 6. Kernel de FluxPress

Este es el **cerebro del sistema**.

```php
<?php

final class Kernel
{
    public Container $container;

    public function bootstrap()
    {
        $this->container = new Container();

        $this->registerServices();
        $this->bootPlugins();
        $this->bootRoutes();
    }

    protected function registerServices()
    {
        $this->container->singleton('events', fn() => new EventBus());
        $this->container->singleton('queue', fn() => new Queue());
        $this->container->singleton('router', fn() => new Router());
        $this->container->singleton('rewrite', fn() => new RewriteEngine());

        LegacyHooks::setBus($this->container->make('events'));
    }

    protected function bootPlugins()
    {
        $plugins = new PluginManager(__DIR__ . '/../plugins');
        $plugins->load();
    }

    protected function bootRoutes()
    {
        $router = $this->container->make('router');

        require __DIR__ . '/../routes/web.php';
    }

    public function dispatch()
    {
        $router = $this->container->make('router');

        $router->dispatch(
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI']
        );

        $this->container->make('queue')->run();
    }
}
```

---

# 7. Flujo de ejecución de FluxPress

```text
HTTP Request
     ↓
Rewrite Engine
     ↓
Router
     ↓
Controller / Plugin
     ↓
EventBus
     ↓
Queue Jobs
     ↓
Response
```

---

# 8. Ejemplo plugin moderno

```php
<?php

$events = kernel()->events;

$events->subscribe('post.created', function($post) {
    echo "Indexing post...";
});

// O con facade

Event::subscribe('post.created', function($post) {
    echo "Indexing post...";
});
```

---

# 9. Ejemplo plugin legacy WordPress

```php
<?php

add_action('init', function() {
    echo "Legacy plugin loaded";
});
```
