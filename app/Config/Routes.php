<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
// $routes->resource('api');
$routes->get('api', 'Api::index');
$routes->get('api/test', 'Api::test');
$routes->get('api/(:num)', 'Api::show/$1');
$routes->post('api', 'Api::create');
$routes->put('api/(:num)', 'Api::update/$1');
$routes->delete('api/(:num)', 'Api::delete/$1');
