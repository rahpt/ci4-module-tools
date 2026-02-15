<?php

$routes->group('system/modules', ['namespace' => 'App\Modules\ModuleManager\Controllers'], function($routes) {
    $routes->get('/', 'ModuleController::index');
    $routes->get('toggle/(:segment)', 'ModuleController::toggle/$1');
    $routes->get('marketplace', 'ModuleController::marketplace');
    $routes->post('install', 'ModuleController::install');
    $routes->post('uninstall', 'ModuleController::uninstall');
});
