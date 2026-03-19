<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    private function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $headers,
        private readonly array $query,
        private readonly array $body,
        private readonly string $rawBody,
        private readonly string $requestId
    ) {
    }

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';

        $headers = self::collectHeaders();
        $query = $_GET;

        $rawBody = file_get_contents('php://input');
        if ($rawBody === false) {
            $rawBody = '';
        }

        $contentType = $headers['content-type'] ?? '';
        $body = self::parseBody($rawBody, $contentType);

        $requestId = $headers['x-request-id'] ?? bin2hex(random_bytes(8));

        return new self(
            $method,
            self::normalizePath($path),
            $headers,
            $query,
            $body,
            $rawBody,
            $requestId
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function requestId(): string
    {
        return $this->requestId;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function queryAll(): array
    {
        return $this->query;
    }

    public function bodyAll(): array
    {
        return $this->body;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->body)) {
            return $this->body[$key];
        }

        return $this->query[$key] ?? $default;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $normalized = strtolower($name);

        return $this->headers[$normalized] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $authorization = (string) ($this->header('authorization', ''));
        if ($authorization === '') {
            return null;
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    public function rawBody(): string
    {
        return $this->rawBody;
    }

    private static function normalizePath(string $path): string
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

    private static function parseBody(string $rawBody, string $contentType): array
    {
        if ($rawBody === '') {
            return [];
        }

        if (str_contains(strtolower($contentType), 'application/json')) {
            $decoded = json_decode($rawBody, true);

            if (!is_array($decoded)) {
                throw new ApiException(
                    400,
                    'invalid_json',
                    'Request body must be a valid JSON object.'
                );
            }

            return $decoded;
        }

        if (str_contains(strtolower($contentType), 'application/x-www-form-urlencoded')) {
            parse_str($rawBody, $parsed);
            return is_array($parsed) ? $parsed : [];
        }

        return [];
    }

    private static function collectHeaders(): array
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            $raw = getallheaders();
            if (is_array($raw)) {
                foreach ($raw as $name => $value) {
                    $headers[strtolower((string) $name)] = (string) $value;
                }
            }

            return $headers;
        }

        foreach ($_SERVER as $key => $value) {
            if (!str_starts_with($key, 'HTTP_')) {
                continue;
            }

            $name = strtolower(str_replace('_', '-', substr($key, 5)));
            $headers[$name] = (string) $value;
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = (string) $_SERVER['CONTENT_LENGTH'];
        }

        return $headers;
    }
}
