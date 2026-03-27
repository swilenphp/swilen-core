<?php

namespace Swilen\Routing\Compiler;

use Swilen\Routing\RouteMatching;

/**
 * Compiles a single route pattern into its constituent parts.
 *
 * Responsible ONLY for transforming one URI pattern string (e.g. "/users/{int:id}")
 * into a structured descriptor that the DataGenerator can consume.
 * It reuses the existing RouteMatching::compile() for typed-parameter expansion.
 */
final class RouteCompiler
{
    /**
     * Analyse a URI pattern and return a compiled route descriptor.
     *
     * A route is "static" when its URI contains no parameter placeholders.
     *
     * Returned shape:
     * [
     *   'isStatic' => bool,
     *   'uri'      => string,           // original URI (static routes only need this)
     *   'regex'    => string,           // partial regex WITHOUT anchors (dynamic only)
     *   'vars'     => list<string>,     // ordered parameter names  (dynamic only)
     * ]
     *
     * @param string $uri
     *
     * @return array{isStatic: bool, uri: string, regex: string, vars: list<string>}
     */
    public static function compile(string $uri): array
    {
        // Fast path: no placeholder characters present → purely static
        if (strpos($uri, '{') === false) {
            return [
                'isStatic' => true,
                'uri'      => $uri,
                'regex'    => '',
                'vars'     => [],
            ];
        }

        // Delegate typed-parameter expansion to the existing RouteMatching helper.
        // RouteMatching::compile() returns ['regex' => string, 'vars' => string[]]
        $compiled = RouteMatching::compile($uri);

        return [
            'isStatic' => false,
            'uri'      => $uri,
            'regex'    => $compiled['regex'],
            'vars'     => $compiled['vars'],
        ];
    }
}
