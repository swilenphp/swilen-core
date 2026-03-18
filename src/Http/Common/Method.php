<?php

namespace Swilen\Http\Common;

enum Method: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case TRACE = 'TRACE';
    case CONNECT = 'CONNECT';

        // WebDAV
    case PROPFIND = 'PROPFIND';
    case PROPPATCH = 'PROPPATCH';
    case MKCOL = 'MKCOL';
    case COPY = 'COPY';
    case MOVE = 'MOVE';
    case LOCK = 'LOCK';
    case UNLOCK = 'UNLOCK';

        // WebDAV extensions
    case REPORT = 'REPORT';
    case MKACTIVITY = 'MKACTIVITY';
    case CHECKOUT = 'CHECKOUT';
    case MERGE = 'MERGE';
    case SEARCH = 'SEARCH';

        // Others
    case PURGE = 'PURGE';
    case LINK = 'LINK';
    case UNLINK = 'UNLINK';
}
