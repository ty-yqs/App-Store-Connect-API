<?php

declare(strict_types=1);

require_once __DIR__ . '/app/Support/Env.php';

App\Support\Env::load(__DIR__ . '/.env');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativePath = substr($class, strlen($prefix));
    $filePath = __DIR__ . '/app/' . str_replace('\\', '/', $relativePath) . '.php';

    if (is_file($filePath)) {
        require_once $filePath;
    }
});

$vendorAutoload = __DIR__ . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}

date_default_timezone_set(App\Support\Env::get('APP_TIMEZONE', 'UTC'));
