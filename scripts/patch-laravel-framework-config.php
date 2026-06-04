<?php

$path = dirname(__DIR__).'/vendor/laravel/framework/config/database.php';

if (! file_exists($path)) {
    return;
}

$contents = file_get_contents($path);

if ($contents === false) {
    return;
}

$replacement = "                (defined('Pdo\\Mysql::ATTR_SSL_CA') ? constant('Pdo\\Mysql::ATTR_SSL_CA') : \\PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),";

$patched = preg_replace(
    "/^\s*.*MYSQL_ATTR_SSL_CA.*$/m",
    $replacement,
    $contents,
);

if ($patched !== null && $patched !== $contents) {
    file_put_contents($path, $patched);
}
