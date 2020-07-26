<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


    // return $router->app->version();
$router->post('/' . env('BOT_TOKEN'), ['uses' => 'Controller@route']);

// $router->get('/', function () use ($router) {
    // return "HEY!";
// });

$router->get('/test', function () use ($router) {
    return "HEY!";
});
