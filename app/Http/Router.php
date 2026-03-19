<?php

declare(strict_types=1);

namespace App\Http;

final class Router
{
    /** @var array<int, array{method: string, pattern: string, regex: string, handler: callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function patch(string $pattern, callable $handler): void
    {
        $this->add('PATCH', $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    public function options(string $pattern, callable $handler): void
    {
        $this->add('OPTIONS', $pattern, $handler);
    }

    public function add(string $method, string $pattern, callable $handler): void
    {
        $normalizedPattern = $this->normalizePath($pattern);

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $normalizedPattern,
            'regex' => $this->toRegex($normalizedPattern),
            'handler' => $handler,
        ];
    }

    /**
     * @return array{0: callable, 1: array<string, string>}
     */
    public function resolve(Request $request): array
    {
        $method = strtoupper($request->method());
        $path = $request->path();

        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            if ($route['method'] !== $method) {
                $allowedMethods[] = $route['method'];
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (!is_string($key)) {
                    continue;
                }

                $params[$key] = urldecode($value);
            }

            return [$route['handler'], $params];
        }

        if ($allowedMethods !== []) {
            throw new ApiException(
                405,
                'method_not_allowed',
                'HTTP method is not allowed for this route.',
                ['allow' => implode(', ', array_unique($allowedMethods))]
            );
        }

        throw new ApiException(404, 'not_found', 'Requested route was not found.');
    }

    private function normalizePath(string $path): string
    {
        if ($path === '') {
            return '/';
        }

        $normalized = '/' . ltrim($path, '/');
        if ($normalized !== '/') {
            $normalized = rtrim($normalized, '/');
        }

        return $normalized === '' ? '/' : $normalized;
    }

    private function toRegex(string $pattern): string
    {
        $escaped = preg_replace_callback('/\\{([a-zA-Z_][a-zA-Z0-9_]*)\\}/', static function (array $matches): string {
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, preg_quote($pattern, '#'));

        if (!is_string($escaped)) {
            throw new ApiException(500, 'router_compile_error', 'Failed to compile route pattern.');
        }

        return '#^' . $escaped . '/?$#';
    }
}
