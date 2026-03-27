<?php

namespace Swilen\Routing;

class RouteMatching
{
    /**
     * The predefined types for route parameters.
     * In routes use with {type:name} syntax, where type is one of the keys in this array.
      *
      * @var array<string, string>
     */
    private const TYPES = [
        'int'    => '\d+',
        'alpha'  => '[a-zA-Z_\-]+',
        'string' => '[a-zA-Z0-9_\-]+',
        'slug'   => '[a-z0-9\-]+',
        'uuid'   => '[0-9a-fA-F\-]{36}',
        'any'    => '[^/]+',
    ];

    /**
     * Create regex from given pattern.
     *
     * @param string $pattern
     *
     * @return array{regex: string, vars: array<int, string>}
     */
    public static function compile(string $pattern): array
    {
        $variables = [];

        $compiled = preg_replace_callback('/\{([a-z]+:)?([a-zA-Z0-9_]+)\}/', function ($matches) use (&$variables) {
            $type = rtrim($matches[1] ?? 'any:', ':');
            $name = $matches[2];

            $variables[] = $name;

            $regexPart = self::TYPES[$type] ?? self::TYPES['any'];
            return '(' . $regexPart . ')';
        }, $pattern);

        return [
            'regex' => $compiled,
            'vars'  => $variables
        ];
    }
}
