<?php

$routes->group('dashboard', ['namespace' => 'App\Modules\Dashboard\Controllers', 'filter' => 'session'], function($routes) {
    $routes->get('', 'DashboardController::index');
});
