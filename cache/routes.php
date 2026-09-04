<?php

use Rnr\Routing\RouteType;

return array (
  0 => 
  array (
    'params' => 
    array (
    ),
    'match' => 'login',
    'method' => 'ANY',
    'type' => 'Static',
    'middleware' => 
    array (
    ),
    'controller' => 'UserController',
    'methodName' => 'login',
  ),
  1 => 
  array (
    'params' => 
    array (
    ),
    'match' => 'logout',
    'method' => 'GET',
    'type' => 'Static',
    'middleware' => 
    array (
      0 => 'user',
    ),
    'controller' => 'UserController',
    'methodName' => 'logout',
  ),
  2 => 
  array (
    'params' => 
    array (
    ),
    'match' => 'register',
    'method' => 'GET',
    'type' => 'Static',
    'middleware' => 
    array (
    ),
    'controller' => 'UserController',
    'methodName' => 'register',
  ),
  3 => 
  array (
    'params' => 
    array (
      0 => 'id',
      1 => 'page',
    ),
    'match' => NULL,
    'method' => 'GET',
    'type' => 'Parametric',
    'segments' => 
    array (
      0 => 
      array (
        'type' => 'static',
        'value' => 'documents',
      ),
      1 => 
      array (
        'type' => 'param',
        'name' => 'id',
        'optional' => false,
      ),
      2 => 
      array (
        'type' => 'param',
        'name' => 'page',
        'optional' => true,
      ),
    ),
    'fastParametric' => true,
    'prefix' => 'documents/',
    'requiredParams' => 1,
    'optionalParams' => 1,
    'middleware' => 
    array (
    ),
    'controller' => 'documents',
    'methodName' => 'view',
  ),
  4 => 
  array (
    'params' => 
    array (
      0 => 'id_article',
    ),
    'match' => NULL,
    'method' => 'GET',
    'type' => 'Regex',
    'regex' => '#.*-a(\\d+)\\.html#',
    'middleware' => 
    array (
    ),
    'controller' => 'HomeController',
    'methodName' => 'Article',
  ),
  5 => 
  array (
    'params' => 
    array (
    ),
    'match' => 'admin/logout',
    'method' => 'GET',
    'type' => 'Static',
    'middleware' => 
    array (
      0 => 'admin',
    ),
    'controller' => 'Admin\\AdminController',
    'methodName' => 'logout',
  ),
  6 => 
  array (
    'params' => 
    array (
      0 => 'id',
      1 => 'page',
    ),
    'match' => NULL,
    'method' => 'ANY',
    'type' => 'Wildcard',
    'prefix' => 'admin',
    'namespace' => 'Admin',
    'middleware' => 
    array (
      0 => 'admin',
    ),
    'controller' => 'Admin\\*',
  ),
  7 => 
  array (
    'params' => 
    array (
      0 => 'id',
      1 => 'page',
    ),
    'match' => NULL,
    'method' => 'ANY',
    'type' => 'Fallback',
    'middleware' => 
    array (
      0 => 'user',
    ),
    'controller' => '*',
  ),
);