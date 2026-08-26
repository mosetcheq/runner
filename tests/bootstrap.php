<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';   // Composer
require __DIR__ . '/../runner/src/core/autoloader.php';
use Rnr\Core\Autoloader;


Autoloader::addMap([
    'Rnr\\' => Config\Path::RNR,
//    'App\\' => Config\Path::APP . 'src/'
]);