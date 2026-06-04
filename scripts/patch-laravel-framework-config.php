<?php

$path = dirname(__DIR__).'/vendor/laravel/framework/config/database.php';

if (! file_exists($path)) {
    return;
}

$contents = file_get_contents($path);

if ($contents === false) {
    return;
}

$patched = str_replace(
    'PDO::MYSQL_ATTR_SSL_CA',
    "defined('Pdo\\\\Mysql::ATTR_SSL_CA') ? constant('Pdo\\\\Mysql::ATTR_SSL_CA') : \\PDO::MYSQL_ATTR_SSL_CA",
    $contents,
);

if ($patched !== $contents) {
    file_put_contents($path, $patched);
}
