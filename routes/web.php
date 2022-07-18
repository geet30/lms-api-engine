<?php

use App\Http\Controllers\V1\Plans\Mobile\MobilePlanController;
use App\Http\Controllers\V1\Plans\Energy\EnergyPlanController;
/** @var \Laravel\Lumen\Routing\Router $router */

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


$router->get('/', function () use ($router) {
    $router->group(['middleware' => 'cors'], function () use ($router) {
    return $router->app->version();
});
});

