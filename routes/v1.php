<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Version One Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/





/**  Write those API's here which need token authntications **/
$router->group(['middleware' => 'auth'], function () use ($router) {
    /* Save Visitor Information */
    $router->post('save-visitor-information', 'Visitor\VisitorController@saveVisit');

    /** Get Life Support Content **/
    $router->get('life-support-content', 'Visitor\LifeSupportController@getLifeSupportContent');

    /** Get Life Support Equipment **/
    $router->get('life-support-equipment', 'Visitor\LifeSupportController@getLifeSupportEquipment');
});
