<?php

namespace Swilen\Routing;

class RouteNode
{
    public string $prefix;

    /** @var array<string,RouteNode> */
    public array $staticChildren = [];

    public ?RouteNode $paramChild = null;

    public ?RouteNode $wildcardChild = null;

    public ?string $paramName = null;

    public ?\Closure $validator = null;

    public ?\Closure $handler = null;

    public RouteTypeKind $type;

    public function __construct(
        string $prefix = '',
        ?\Closure $handler = null,
        RouteTypeKind $type = RouteTypeKind::REGULAR,
        ?string $paramName = null
    ) {
        $this->prefix = $prefix;
        $this->handler = $handler;
        $this->type = $type;
        $this->paramName = $paramName;
    }
}
