<?php

$routes->group('system/modules', ['namespace' => 'App\Modules\Modules\Controllers'], static function($routes) {
    $routes->get('/', 'ModuleController::index');
    $routes->post('activate/(:segment)', 'ModuleController::activate/$1');
    $routes->post('deactivate/(:segment)', 'ModuleController::deactivate/$1');
    $routes->post('delete/(:segment)', 'ModuleController::delete/$1');
    $routes->get('install', 'ModuleController::install');
    $routes->post('install', 'ModuleController::processInstall');
});
