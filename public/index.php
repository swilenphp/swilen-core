<?php

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

$app->make('router')->get('/', function () {
    return 'Hello World';
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
