<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('__module__', ['namespace' => 'App\Modules\__Module__\Controllers', 'filter' => 'session'], static function ($routes) {
    $routes->get('/', '__Module__Controller::index');
});
