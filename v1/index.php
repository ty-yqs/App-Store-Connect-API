<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Http\ApiException;
use App\Http\JsonResponder;
use App\Http\Request;
use App\Http\Router;

$requestId = bin2hex(random_bytes(8));

try {
    $request = Request::capture();
    $requestId = $request->requestId();

    header('X-Request-Id: ' . $requestId);
    header('Cache-Control: no-store');

    if ($request->method() === 'OPTIONS') {
        http_response_code(204);
        exit();
    }

    $router = new Router();

    $registerRoutes = require __DIR__ . '/routes.php';
    if (!is_callable($registerRoutes)) {
        throw new ApiException(500, 'routes_bootstrap_error', 'Failed to register routes.');
    }

    $registerRoutes($router);

    [$handler, $params] = $router->resolve($request);
    $result = $handler($request, $params);

    if (!is_array($result) || !array_key_exists('status', $result) || !array_key_exists('data', $result)) {
        throw new ApiException(500, 'invalid_handler_response', 'Route handler returned an invalid response payload.');
    }

    JsonResponder::success((int) $result['status'], $result['data'], $requestId);
} catch (Throwable $throwable) {
    JsonResponder::error($throwable, $requestId);
}
