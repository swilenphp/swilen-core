<?php

namespace Swilen\Routing;

enum RouteTypeKind: int
{
    case REGULAR = 1;
    case PARAM = 2;
    case WILDCARD = 3;
}
