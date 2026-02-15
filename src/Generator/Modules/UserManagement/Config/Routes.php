<?php

$routes->group('system/users', ['namespace' => 'App\Modules\UserManagement\Controllers', 'filter' => 'session'], function($routes) {
    $routes->get('/', 'UserController::index');
    $routes->get('account', 'UserController::account');
});
