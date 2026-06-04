<?php

require __DIR__.'/../vendor/autoload.php';

$envPath = dirname(__DIR__).'/.env';

if (! file_exists($envPath)) {
    file_put_contents($envPath, "APP_KEY=base64:9QO7Wmqf4dP4lpnfS8vK6tD/q4SevBYh2wL3mgsY3Mk=\n");

    register_shutdown_function(static function () use ($envPath): void {
        if (file_exists($envPath)) {
            unlink($envPath);
        }
    });
}
