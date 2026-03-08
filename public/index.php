<?php

use Swilen\Routing\Router;

define('SWILEN_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/
require __DIR__ . '/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Create Swilen application instance
|--------------------------------------------------------------------------
*/
$app = new Swilen\Arthropod\Application(
    $_ENV['SWILEN_BASE_URL'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Register exception handler
|--------------------------------------------------------------------------
*/
$app->singleton(
    \Swilen\Arthropod\Contract\ExceptionHandler::class,
    \Swilen\Arthropod\Exception\Handler::class
);

$app->get(Router::class)->get('/', function () {
    return response()->json([
        'message' => 'Hello World',
    ]);
});

/*
|--------------------------------------------------------------------------
| Create new request instance from PHP superglobals
|--------------------------------------------------------------------------
*/
$request = Swilen\Http\Request::createFromGlobals();

/*
|--------------------------------------------------------------------------
| Handle the incoming request and retrieve the response
|--------------------------------------------------------------------------
*/
$response = $app->handle($request);

/*
|--------------------------------------------------------------------------
| Terminate application response
|--------------------------------------------------------------------------
*/
$response->terminate();
