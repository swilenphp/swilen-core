<?php

namespace Swilen\Routing\Compiler;

use Swilen\Routing\Route;

/**
 * Builds the compiled routing data structures used by FastRouteDispatcher.
 *
 * Inspired by nikic/FastRoute's DataGenerator with Group Count Based (GCB) approach:
 *
 *   - static  : hash-map  METHOD → URI → handler descriptor  (O(1) dispatch)
 *   - dynamic : one big alternation regex per HTTP method, with Group Count Based
 *               offset tracking so a single preg_match() identifies the matched
 *               route AND extracts all named parameters simultaneously.
 *   - named   : name → [ method, uri ]  for URL generation
 *
 * The produced array is fully serialisable (no objects) so it can be written to
 * a PHP file with var_export() and loaded back with a plain require().
 *
 * Compiled data shape
 * -------------------
 * [
 *   'static'  => [
 *       'GET' => [ '/uri' => [ 'handler' => …, 'middleware' => […], 'name' => ?string ] ],
 *       …
 *   ],
 *   'dynamic' => [
 *       'GET' => [
 *           'regex'  => '~^(?:PATTERN1()|PATTERN2()()|…)$~',
 *           'routes' => [
 *               0 => [ 'handler' => …, 'middleware' => […], 'vars' => ['id'], 'name' => ?string ],
 *               1 => [ … ],
 *               …
 *           ],
 *       ],
 *       …
 *   ],
 *   'named'   => [
 *       'user.show' => [ 'method' => 'GET', 'uri' => '/users/{int:id}' ],
 *       …
 *   ],
 * ]
 */
final class DataGenerator
{
    // Maximum number of capturing groups PCRE allows inside one alternative.
    // We use a conservative limit so we never hit PCRE's internal limit (65535).
    private const CHUNK_SIZE = 30;

    /** @var array<string, array<string, array>> Static route map */
    private array $static = [];

    /** @var array<string, list<array>> Dynamic routes per method, before regex assembly */
    private array $dynamic = [];

    /** @var array<string, array{method: string, uri: string}> Named route reverse-lookup */
    private array $named = [];

    /**
     * Register a route with the data generator.
     *
     * @param Route $route
     *
     * @return void
     */
    public function addRoute(Route $route): void
    {
        $method  = $route->getMethod();
        $uri     = $route->getPattern();
        $handler = $route->getAction('uses');
        $mw      = $route->getMiddleware();
        $name    = $route->getName();

        $descriptor = RouteCompiler::compile($uri);

        if ($descriptor['isStatic']) {
            $this->static[$method][$uri] = [
                'handler'    => $handler,
                'middleware' => $mw,
                'name'       => $name,
            ];
        } else {
            $this->dynamic[$method][] = [
                'pattern'    => $uri,
                'regex'      => $descriptor['regex'],
                'vars'       => $descriptor['vars'],
                'handler'    => $handler,
                'middleware' => $mw,
                'name'       => $name,
            ];
        }

        if ($name !== null) {
            $this->named[$name] = ['method' => $method, 'uri' => $uri];
        }
    }

    /**
     * Assemble and return the final compiled data array.
     *
     * Dynamic routes are stitched into per-method Group Count Based regexes.
     *
     * @return array
     */
    public function generate(): array
    {
        return [
            'static'  => $this->static,
            'dynamic' => $this->buildDynamicData(),
            'named'   => $this->named,
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build the dynamic dispatch data for all registered HTTP methods.
     *
     * Each method gets a list of "chunks". A chunk contains:
     *   - 'regex'  : the assembled alternation regex for that chunk
     *   - 'routes' : an ordered array of route descriptors matching each alternative
     *
     * Chunking ensures we never exceed PHP/PCRE's capturing-group limits.
     *
     * @return array<string, list<array{regex: string, routes: list<array>}>>
     */
    private function buildDynamicData(): array
    {
        $result = [];

        foreach ($this->dynamic as $method => $routes) {
            $chunks = array_chunk($routes, self::CHUNK_SIZE);
            $result[$method] = [];

            foreach ($chunks as $chunk) {
                $result[$method][] = $this->buildChunk($chunk);
            }
        }

        return $result;
    }

    /**
     * Build one regex chunk from a list of dynamic route descriptors.
     *
     * Group Count Based technique: every alternative in the big regex is padded
     * with trailing empty-capturing groups so that the TOTAL number of groups
     * up to and including that alternative is always the same multiple of a
     * fixed stride. This lets us compute the matched route index purely from
     * the count of matched groups without scanning through them one by one.
     *
     * The stride = max(vars per route in this chunk) + 1.
     *
     * Example for stride=3:
     *   alt 0 → groups 1..3   → route index 0
     *   alt 1 → groups 4..6   → route index 1
     *
     * @param list<array> $chunk
     *
     * @return array{regex: string, routes: list<array>}
     */
    private function buildChunk(array $chunk): array
    {
        // Determine the stride: max number of capture groups any route needs + 1 sentinel
        $maxVars = 0;
        foreach ($chunk as $route) {
            $varCount = count($route['vars']);
            if ($varCount > $maxVars) {
                $maxVars = $varCount;
            }
        }
        $stride = $maxVars + 1; // +1 for the sentinel group that identifies the route

        $regexParts = [];
        $routeMap   = [];

        foreach ($chunk as $index => $route) {
            $varCount = count($route['vars']);
            // Padding: (stride - varCount - 1) empty groups to reach the sentinel slot
            $padding = str_repeat('()', $stride - $varCount - 1);

            // Each route regex must contribute exactly $varCount capturing groups.
            // We pad it with empty capturing groups () to reach $stride - 1.
            // Then we add the sentinel group () to identify the route.
            $regexParts[] = $route['regex'] . $padding . '()';

            $routeMap[$index] = [
                'handler'    => $route['handler'],
                'middleware' => $route['middleware'],
                'vars'       => $route['vars'],
                'name'       => $route['name'],
                'pattern'    => $route['pattern'],
            ];
        }

        return [
            'regex'  => '~^(?|' . implode('|', $regexParts) . ')$~',
            'stride' => $stride,
            'routes' => $routeMap,
        ];
    }
}
