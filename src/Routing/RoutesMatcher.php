<?php

namespace Swilen\Routing;

use Swilen\Http\Common\Method;

class RoutesMatcher
{
    /**
     * All routes by method.
     *
     * @var array<string,RouteNode>
     */
    protected array $routes = [];

    /**
     * All validators for route parameters.
     *
     * @var array<string,\Closure>
     */
    protected array $validators = [];

    public function __construct()
    {
        $this->routes = [];
        $this->validators = [
            'int'   => fn($v) => ctype_digit($v),
            'alpha' => fn($v) => ctype_alpha($v),
            'alnum' => fn($v) => ctype_alnum($v),
            'slug'  => fn($v) => preg_match('/^[a-z0-9\-]+$/i', $v),
            'uuid'  => fn($v) => preg_match('/^[0-9a-fA-F\-]{36}$/', $v),
            'any'   => fn($v) => true,
        ];
    }

    /**
     * Find longest common prefix of two strings.
     *
     * @param string $a
     * @param string $b
     *
     * @return int
     */
    protected function lcp(string $a, string $b): int
    {
        $max = min(strlen($a), strlen($b));
        for ($i = 0; $i < $max; $i++) {
            if ($a[$i] !== $b[$i]) {
                return $i;
            }
        }

        return $max;
    }

    /**
     * Compile validator for route parameters.
     *
     * @param string $types
     *
     * @return \Closure
     */
    protected function compileValidator(string $types): \Closure
    {
        $types = explode('|', $types);
        return function ($value) use ($types) {

            foreach ($types as $type) {

                if (!isset($this->validators[$type])) {
                    continue;
                }

                if (($this->validators[$type])($value)) {
                    return true;
                }
            }

            return false;
        };
    }

    /**
     * Insert route to matcher.
     *
     * @param \Swilen\Http\Common\Method $method
     * @param string                       $path
     * @param \Closure                     $handler
     *
     * @return void
     */
    public function insert(Method $method, string $path, \Closure $handler): void
    {
        // Create root node if not exists
        if (!isset($this->routes[$method->value])) {
            $this->routes[$method->value] = new RouteNode();
        }

        $node = $this->routes[$method->value];

        while (true) {
            $lcp = $this->lcp($path, $node->prefix);

            // If the current node's prefix is longer than the longest common prefix,
            // we need to split the current node into two nodes.
            if ($lcp < strlen($node->prefix)) {
                $child = new RouteNode(
                    substr($node->prefix, $lcp),
                    $node->handler,
                    $node->type,
                    $node->paramName
                );

                $child->staticChildren = $node->staticChildren;
                $child->paramChild = $node->paramChild;
                $child->wildcardChild = $node->wildcardChild;
                $child->validator = $node->validator;

                $node->prefix = substr($node->prefix, 0, $lcp);
                $node->staticChildren = [];
                if ($child->prefix !== '') {
                    $node->staticChildren[$child->prefix[0]] = $child;
                }

                $node->handler = null;
                $node->paramChild = null;
                $node->wildcardChild = null;
            }

            // If the current node's prefix is shorter than the longest common prefix,
            // we need to move to the next node.
            if ($lcp < strlen($path)) {
                $path = substr($path, $lcp);
                if ($path[0] === '{') {
                    preg_match('/^\{(\w+)(?::([\w|]+))?\}/', $path, $m);

                    $param = $m[1];
                    $types = $m[2] ?? 'any';
                    if (!$node->paramChild) {
                        $child = new RouteNode(
                            '',
                            null,
                            RouteTypeKind::PARAM,
                            $param
                        );

                        $child->validator = $this->compileValidator($types);
                        $node->paramChild = $child;
                    }

                    $node = $node->paramChild;
                    $path = substr($path, strlen($m[0]));

                    continue;
                }

                if ($path[0] === '*') {
                    $param = substr($path, 1);
                    $node->wildcardChild = new RouteNode(
                        '',
                        $handler,
                        RouteTypeKind::WILDCARD,
                        $param
                    );

                    return;
                }

                $char = $path[0];
                if (!isset($node->staticChildren[$char])) {
                    $node->staticChildren[$char] = new RouteNode($path, $handler);

                    return;
                }

                $node = $node->staticChildren[$char];

                continue;
            }

            $node->handler = $handler;

            return;
        }
    }

    /**
     * Lookup route in matcher.
     *
     * @param \Swilen\Http\Common\Method $method
     * @param string                       $path
     *
     * @return array|null
     */
    public function lookup(Method $method, string $path): ?array
    {
        $node = $this->routes[$method->value];
        if (!$node) {
            return null;
        }

        $params = [];
        while (true) {
            if (!str_starts_with($path, $node->prefix)) {
                return null;
            }

            $path = substr($path, strlen($node->prefix));
            if ($path === '') {
                if (!$node->handler) {
                    return null;
                }

                return [
                    'handler' => $node->handler,
                    'params' => $params
                ];
            }

            $char = $path[0];
            if (isset($node->staticChildren[$char])) {
                $node = $node->staticChildren[$char];
                continue;
            }

            if ($node->paramChild) {
                $end = strpos($path, '/');
                if ($end === false) {
                    $end = strlen($path);
                }

                $value = substr($path, 0, $end);
                if (
                    $node->paramChild->validator &&
                    !($node->paramChild->validator)($value)
                ) {
                    return null;
                }

                $params[$node->paramChild->paramName] = $value;
                $path = substr($path, $end);
                $node = $node->paramChild;

                continue;
            }

            if ($node->wildcardChild) {
                $params[$node->wildcardChild->paramName] = $path;

                return [
                    'handler' => $node->wildcardChild->handler,
                    'params' => $params
                ];
            }

            return null;
        }
    }
}
