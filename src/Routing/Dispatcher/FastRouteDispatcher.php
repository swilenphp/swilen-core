<?php

namespace Swilen\Routing\Dispatcher;

use Swilen\Routing\Result\RouteResult;

/**
 * Group Count Based (GCB) dispatcher.
 *
 * Operates exclusively on the plain-array compiled data produced by
 * Swilen\Routing\Compiler\DataGenerator (or loaded from a PHP cache file).
 * There are NO object allocations on the hot dispatch path; only array reads
 * and a single preg_match() for dynamic routes.
 *
 * Algorithm
 * ---------
 * 1. Static lookup  — O(1) hash-map check per method.
 * 2. Dynamic lookup — iterate through regex chunks for the method; each chunk
 *    is one big alternation regex.  The Group Count Based stride lets us
 *    derive the matched route index from (number of matched groups / stride).
 * 3. Method-not-allowed check — if step 1 & 2 failed for the requested method
 *    but we get a static or dynamic hit for ANY other method, we return
 *    METHOD_NOT_ALLOWED with the list of those methods.
 *
 * Compiled data format expected
 * -----------------------------
 * See DataGenerator::generate() for the full shape.  Briefly:
 *
 *   $data['static'][METHOD][URI]  = ['handler'=>…,'middleware'=>…,'name'=>?]
 *   $data['dynamic'][METHOD][]    = ['regex'=>…,'stride'=>N,'routes'=>[…]]
 *   $data['named'][name]          = ['method'=>…,'uri'=>…]
 */
final class FastRouteDispatcher
{
    /** @var array */
    private array $static;

    /** @var array */
    private array $dynamic;

    /** @var array */
    private array $named;

    /**
     * @param array $compiledData  Output of DataGenerator::generate() (or loaded cache).
     */
    public function __construct(array $compiledData)
    {
        $this->static  = $compiledData['static']  ?? [];
        $this->dynamic = $compiledData['dynamic'] ?? [];
        $this->named   = $compiledData['named']   ?? [];
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Dispatch an HTTP request to the compiled route table.
     *
     * @param string $httpMethod  e.g. 'GET'
     * @param string $uri         Decoded URI path, e.g. '/users/42'
     *
     * @return RouteResult
     */
    public function dispatch(string $httpMethod, string $uri): RouteResult
    {
        // ── 1. Static lookup ──────────────────────────────────────────────────
        if (isset($this->static[$httpMethod][$uri])) {
            $r = $this->static[$httpMethod][$uri];

            return RouteResult::found($r['handler'], [], $r['middleware'], $r['name'], $uri);
        }

        // ── 2. Dynamic lookup ─────────────────────────────────────────────────
        $result = $this->dispatchDynamic($httpMethod, $uri);
        if ($result !== null) {
            return $result;
        }

        // ── 3. Method-not-allowed check ───────────────────────────────────────
        $allowed = $this->findAllowedMethods($httpMethod, $uri);
        if ($allowed !== []) {
            return RouteResult::methodNotAllowed(array_values($allowed));
        }

        return RouteResult::notFound();
    }

    /**
     * Resolve a named route back to its URI pattern.
     *
     * @param string $name
     *
     * @return array{method: string, uri: string}|null
     */
    public function reverse(string $name): ?array
    {
        return $this->named[$name] ?? null;
    }

    // -------------------------------------------------------------------------
    // Private helpers — designed for zero extra allocation on hot path
    // -------------------------------------------------------------------------

    /**
     * Try dynamic routes for the given method.
     *
     * @param string $method
     * @param string $uri
     *
     * @return RouteResult|null
     */
    private function dispatchDynamic(string $method, string $uri): ?RouteResult
    {
        if (!isset($this->dynamic[$method])) {
            return null;
        }

        foreach ($this->dynamic[$method] as $chunk) {
            if (!preg_match($chunk['regex'], $uri, $matches)) {
                continue;
            }

            return $this->extractDynamicResult($chunk, $matches);
        }

        return null;
    }

    /**
     * Extract a RouteResult from a successful preg_match against a GCB chunk.
     *
     * With the Group Count Based layout every route alternative occupies exactly
     * $chunk['stride'] capturing groups.  The sentinel group (the last one of
     * each stride block) is the first non-empty group AFTER all the variable
     * groups.  We find the route index by scanning from the right:
     * the sentinel group for route[i] is at position (i+1)*stride.
     * We count how many values are in $matches (minus $matches[0]) and divide
     * by stride.
     *
     * @param array $chunk    One chunk from compiled dynamic data.
     * @param array $matches  Output of preg_match().
     *
     * @return RouteResult
     */
    private function extractDynamicResult(array $chunk, array $matches): RouteResult
    {
        $stride = $chunk['stride'];

        // matches[0] is the full match; the rest are captured groups.
        // Number of non-empty trailing groups minus the sentinel tells us
        // which alternative fired. We find the route index by locating the
        // last non-empty group: index = ceil(count / stride) - 1.
        // But a simpler O(1) approach: since each alternative fills exactly
        // $stride groups (some may be empty strings ''), we walk backward
        // from the end to find the sentinel group (the first non-'' from right).
        // In GCB with (?|), we find the route index by looking at which sentinel group matched.
        // Each route has its sentinel group at the end of its stride.
        // We find the last group that is NOT NULL/empty.
        $count = count($matches) - 1;
        $idx = $count;
        while ($idx > 0 && ($matches[$idx] === '')) {
            $idx--;
        }

        // The route index is the (sentinel_position / stride) - 1
        $routeIndex = (int)(($idx - 1) / $stride);

        $route = $chunk['routes'][$routeIndex];

        // Extract named variable values: groups 1..varCount within the stride block
        $vars       = $route['vars'];
        $varCount   = count($vars);
        $groupOffset = $routeIndex * $stride + 1; // 1-based within $matches

        $parameters = [];
        for ($i = 0; $i < $varCount; $i++) {
            $parameters[$vars[$i]] = $matches[$groupOffset + $i];
        }

        return RouteResult::found(
            $route['handler'],
            $parameters,
            $route['middleware'],
            $route['name'],
            $route['pattern'],
        );
    }

    /**
     * Scan all other HTTP methods for any match to determine allowed methods.
     *
     * This is only called on miss, so performance here is secondary.
     *
     * @param string $requestedMethod
     * @param string $uri
     *
     * @return string[]
     */
    private function findAllowedMethods(string $requestedMethod, string $uri): array
    {
        $allowed = [];

        // Check static table for other methods
        foreach ($this->static as $method => $uriMap) {
            if ($method !== $requestedMethod && isset($uriMap[$uri])) {
                $allowed[$method] = $method;
            }
        }

        // Check dynamic chunks for other methods
        foreach ($this->dynamic as $method => $chunks) {
            if ($method === $requestedMethod) {
                continue;
            }

            foreach ($chunks as $chunk) {
                if (preg_match($chunk['regex'], $uri)) {
                    $allowed[$method] = $method;
                    break; // one hit is enough for this method
                }
            }
        }

        return $allowed;
    }
}
