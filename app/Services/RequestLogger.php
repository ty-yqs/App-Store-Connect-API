<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\ApiException;
use App\Http\Request;
use App\Support\Env;
use Throwable;

final class RequestLogger
{
    private const LEVEL_DEBUG = 10;
    private const LEVEL_INFO = 20;
    private const LEVEL_WARN = 30;
    private const LEVEL_ERROR = 40;

    private const MAX_STRING_LENGTH = 1024;

    /** @var array<int, string> */
    private const SENSITIVE_KEYWORDS = [
        'authorization',
        'token',
        'secret',
        'password',
        'passwd',
        'api_key',
        'apikey',
        'private_key',
        'key',
    ];

    private static ?string $requestIdContext = null;

    public static function setRequestIdContext(string $requestId): void
    {
        self::$requestIdContext = trim($requestId) === '' ? null : $requestId;
    }

    public static function clearContext(): void
    {
        self::$requestIdContext = null;
    }

    public static function logInbound(Request $request, ?string $requestId = null): void
    {
        self::write('info', [
            'event' => 'inbound',
            'request_id' => self::resolveRequestId($requestId, $request->requestId()),
            'method' => strtoupper($request->method()),
            'path' => $request->path(),
            'query' => self::sanitizeArray($request->queryAll()),
            'body' => self::sanitizeArray($request->bodyAll()),
            'headers' => self::sanitizeArray($request->headersAll()),
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    public static function logOutbound(
        Request $request,
        int $statusCode,
        mixed $data,
        float $durationMs,
        ?string $requestId = null
    ): void {
        self::write('info', [
            'event' => 'outbound',
            'request_id' => self::resolveRequestId($requestId, $request->requestId()),
            'method' => strtoupper($request->method()),
            'path' => $request->path(),
            'status' => $statusCode,
            'duration_ms' => (int) round($durationMs),
            'response' => self::summarizeData($data),
        ]);
    }

    public static function logError(
        Throwable $throwable,
        string $requestId,
        ?Request $request = null,
        float $durationMs = 0.0
    ): void {
        $payload = [
            'event' => 'error',
            'request_id' => self::resolveRequestId($requestId),
            'duration_ms' => (int) round($durationMs),
            'error_type' => get_class($throwable),
            'error_message' => self::truncate($throwable->getMessage()),
        ];

        if ($request !== null) {
            $payload['method'] = strtoupper($request->method());
            $payload['path'] = $request->path();
        }

        if ($throwable instanceof ApiException) {
            $payload['status'] = $throwable->statusCode();
            $payload['error_code'] = $throwable->errorCode();
            $payload['error_details'] = self::sanitize($throwable->details(), 'details');
        }

        self::write('error', $payload);
    }

    public static function logUpstreamAttempt(string $method, string $url, int $attempt, int $maxAttempts): void
    {
        if (!self::isHttpLoggingEnabled()) {
            return;
        }

        self::write('debug', [
            'event' => 'upstream_attempt',
            'request_id' => self::resolveRequestId(null),
            'method' => strtoupper($method),
            'url' => self::sanitizeUrl($url),
            'attempt' => $attempt,
            'max_attempts' => $maxAttempts,
        ]);
    }

    public static function logUpstreamResult(
        string $method,
        string $url,
        int $attempt,
        int $maxAttempts,
        int $status,
        ?string $networkError,
        float $durationMs
    ): void {
        if (!self::isHttpLoggingEnabled()) {
            return;
        }

        $level = ($networkError !== null || $status >= 400) ? 'warn' : 'info';

        self::write($level, [
            'event' => 'upstream_result',
            'request_id' => self::resolveRequestId(null),
            'method' => strtoupper($method),
            'url' => self::sanitizeUrl($url),
            'attempt' => $attempt,
            'max_attempts' => $maxAttempts,
            'status' => $status,
            'network_error' => $networkError === null ? null : self::truncate($networkError),
            'duration_ms' => (int) round($durationMs),
        ]);
    }

    public static function elapsedMilliseconds(float $startedAt): float
    {
        return max(0.0, (microtime(true) - $startedAt) * 1000);
    }

    private static function write(string $level, array $payload): void
    {
        if (!self::isEnabled() || !self::canWriteLevel($level)) {
            return;
        }

        $linePayload = [
            'time' => gmdate('c'),
            'level' => $level,
            ...$payload,
        ];

        $line = json_encode($linePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($line)) {
            return;
        }

        try {
            self::writeToFile($line);
        } catch (Throwable) {
            // Keep request handling resilient even if file logging fails.
        }

        try {
            self::writeToStderr($line);
        } catch (Throwable) {
            // Best-effort fallback only.
        }
    }

    private static function writeToFile(string $line): void
    {
        $filePath = trim(Env::get('LOG_FILEPATH', dirname(__DIR__, 2) . '/logs/requests.log'));
        if ($filePath === '') {
            return;
        }

        $directory = dirname($filePath);
        if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
            return;
        }

        @file_put_contents($filePath, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private static function writeToStderr(string $line): void
    {
        if (!Env::bool('LOG_STDERR_ENABLED', true)) {
            return;
        }

        @error_log($line);
    }

    private static function isEnabled(): bool
    {
        return Env::bool('LOG_ENABLED', true);
    }

    private static function isHttpLoggingEnabled(): bool
    {
        return Env::bool('LOG_HTTP_ENABLED', true);
    }

    private static function canWriteLevel(string $level): bool
    {
        $configured = strtolower(Env::get('LOG_LEVEL', 'info'));

        return self::levelToValue($level) >= self::levelToValue($configured);
    }

    private static function levelToValue(string $level): int
    {
        return match (strtolower($level)) {
            'debug' => self::LEVEL_DEBUG,
            'warn', 'warning' => self::LEVEL_WARN,
            'error' => self::LEVEL_ERROR,
            default => self::LEVEL_INFO,
        };
    }

    private static function resolveRequestId(?string $requestId, ?string $fallback = null): ?string
    {
        $candidates = [$requestId, $fallback, self::$requestIdContext];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }

            $normalized = trim($candidate);
            if ($normalized !== '') {
                self::$requestIdContext = $normalized;
                return $normalized;
            }
        }

        return null;
    }

    private static function sanitizeUrl(string $url): string
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return self::truncate($url);
        }

        $path = (string) ($parts['path'] ?? '/');

        $queryString = '';
        if (isset($parts['query']) && $parts['query'] !== '') {
            parse_str((string) $parts['query'], $queryArray);
            $sanitizedQuery = self::sanitizeArray(is_array($queryArray) ? $queryArray : []);
            $queryString = http_build_query($sanitizedQuery, '', '&', PHP_QUERY_RFC3986);
        }

        return $queryString === '' ? $path : $path . '?' . $queryString;
    }

    private static function summarizeData(mixed $data): mixed
    {
        if ($data === null) {
            return null;
        }

        if (is_array($data)) {
            return [
                'type' => 'array',
                'count' => count($data),
                'keys' => array_slice(array_values(array_map('strval', array_keys($data))), 0, 10),
            ];
        }

        if (is_string($data)) {
            return [
                'type' => 'string',
                'length' => strlen($data),
            ];
        }

        if (is_object($data)) {
            return ['type' => 'object', 'class' => get_class($data)];
        }

        return [
            'type' => gettype($data),
            'value' => self::sanitize($data, 'value'),
        ];
    }

    private static function sanitizeArray(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            $safeKey = is_string($key) ? $key : (string) $key;
            $sanitized[$safeKey] = self::sanitize($value, $safeKey);
        }

        return $sanitized;
    }

    private static function sanitize(mixed $value, string $key): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $nestedKey => $nestedValue) {
                $safeKey = is_string($nestedKey) ? $nestedKey : (string) $nestedKey;
                $result[$safeKey] = self::sanitize($nestedValue, $safeKey);
            }

            return $result;
        }

        if (is_object($value)) {
            return ['type' => 'object', 'class' => get_class($value)];
        }

        if (!is_scalar($value) && $value !== null) {
            return self::truncate((string) $value);
        }

        if ($value === null) {
            return null;
        }

        $stringValue = (string) $value;

        if (self::isSensitiveKey($key)) {
            return self::mask($stringValue);
        }

        return self::truncate($stringValue);
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::SENSITIVE_KEYWORDS as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private static function mask(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        if (str_starts_with(strtolower($trimmed), 'bearer ')) {
            $token = trim(substr($trimmed, 7));
            return 'Bearer ' . self::maskValue($token);
        }

        return self::maskValue($trimmed);
    }

    private static function maskValue(string $value): string
    {
        $length = strlen($value);

        if ($length <= 6) {
            return str_repeat('*', max(3, $length));
        }

        return substr($value, 0, 4) . '***' . substr($value, -2);
    }

    private static function truncate(string $value): string
    {
        if (strlen($value) <= self::MAX_STRING_LENGTH) {
            return $value;
        }

        return substr($value, 0, self::MAX_STRING_LENGTH) . '...';
    }
}
