<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\ApiException;
use App\Support\Env;

final class AppleApiClient
{
    private string $baseUrl;
    private int $connectTimeoutSeconds;
    private int $readTimeoutSeconds;
    private int $maxRetries;
    private int $retryDelayMs;
    /** @var array<int, int> */
    private array $retryableStatusCodes;

    public function __construct(
        ?string $baseUrl = null,
        ?int $connectTimeoutSeconds = null,
        ?int $readTimeoutSeconds = null,
        ?int $maxRetries = null,
        ?int $retryDelayMs = null,
        ?array $retryableStatusCodes = null
    )
    {
        $configuredBaseUrl = $baseUrl ?? Env::get('ASC_BASE_URL', 'https://api.appstoreconnect.apple.com');
        $legacyTimeout = Env::int('ASC_HTTP_TIMEOUT', 30);

        $configuredConnectTimeout = $connectTimeoutSeconds
            ?? Env::int('ASC_HTTP_CONNECT_TIMEOUT', min(max($legacyTimeout, 1), 10));
        $configuredReadTimeout = $readTimeoutSeconds
            ?? Env::int('ASC_HTTP_READ_TIMEOUT', max($legacyTimeout, 1));

        $configuredMaxRetries = $maxRetries ?? Env::int('ASC_HTTP_MAX_RETRIES', 0);
        $configuredRetryDelayMs = $retryDelayMs ?? Env::int('ASC_HTTP_RETRY_DELAY_MS', 200);
        $configuredRetryableStatusCodes = $retryableStatusCodes
            ?? self::parseStatusCodeList(Env::get('ASC_HTTP_RETRYABLE_CODES', '408,429,500,502,503,504'));

        $this->baseUrl = rtrim($configuredBaseUrl, '/');
        $this->connectTimeoutSeconds = max(1, $configuredConnectTimeout);
        $this->readTimeoutSeconds = max(1, $configuredReadTimeout);
        $this->maxRetries = max(0, min($configuredMaxRetries, 5));
        $this->retryDelayMs = max(0, $configuredRetryDelayMs);
        $this->retryableStatusCodes = $configuredRetryableStatusCodes === []
            ? [408, 429, 500, 502, 503, 504]
            : array_values(array_unique($configuredRetryableStatusCodes));
    }

    public function request(
        string $method,
        string $path,
        string $bearerToken,
        array $query = [],
        ?array $payload = null
    ): array {
        if (trim($bearerToken) === '') {
            throw new ApiException(401, 'unauthorized', 'Authorization Bearer token is required.');
        }

        $url = $this->buildUrl($path, $query);
        $encodedPayload = null;
        if ($payload !== null) {
            $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encodedPayload === false) {
                throw new ApiException(500, 'payload_encode_error', 'Failed to encode request payload.');
            }
        }

        $attempts = 0;
        $maxAttempts = 1 + ($this->isIdempotentMethod($method) ? $this->maxRetries : 0);

        while ($attempts < $maxAttempts) {
            $attempts++;

            RequestLogger::logUpstreamAttempt($method, $url, $attempts, $maxAttempts);
            $attemptStartedAt = microtime(true);
            $attemptResult = $this->executeRequestAttempt($url, $method, $bearerToken, $encodedPayload);
            RequestLogger::logUpstreamResult(
                $method,
                $url,
                $attempts,
                $maxAttempts,
                $attemptResult['status'],
                $attemptResult['network_error'],
                RequestLogger::elapsedMilliseconds($attemptStartedAt)
            );

            if ($attemptResult['network_error'] === null && $attemptResult['status'] < 400) {
                return [
                    'status' => $attemptResult['status'],
                    'data' => $this->parseResponseBody($attemptResult['raw_response'], $attemptResult['status']),
                ];
            }

            $isRetryable = $this->shouldRetryAttempt(
                $method,
                $attemptResult['status'],
                $attemptResult['network_error'],
                $attempts,
                $maxAttempts
            );

            if ($isRetryable) {
                $this->sleepBeforeRetry($this->backoffDelayMs($attempts));
                continue;
            }

            if ($attemptResult['network_error'] !== null) {
                throw new ApiException(
                    502,
                    'upstream_network_error',
                    'Failed to call App Store Connect API.',
                    [
                        'reason' => $attemptResult['network_error'],
                        'attempts' => $attempts,
                        'max_attempts' => $maxAttempts,
                    ]
                );
            }

            $responseData = $this->parseResponseBody($attemptResult['raw_response'], $attemptResult['status']);
            throw new ApiException(
                $attemptResult['status'],
                'apple_api_error',
                'App Store Connect API returned an error response.',
                [
                    'method' => strtoupper($method),
                    'path' => '/' . ltrim($path, '/'),
                    'query' => $query,
                    'attempts' => $attempts,
                    'max_attempts' => $maxAttempts,
                    'upstream_response' => $responseData,
                ]
            );
        }

        throw new ApiException(500, 'retry_state_error', 'Unexpected retry state while calling App Store Connect API.');
    }

    /**
     * @return array{status: int, raw_response: string, network_error: string|null}
     */
    private function executeRequestAttempt(string $url, string $method, string $bearerToken, ?string $encodedPayload): array
    {
        $curl = curl_init($url);

        if ($curl === false) {
            throw new ApiException(500, 'curl_init_failed', 'Failed to initialize HTTP client.');
        }

        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $bearerToken,
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $this->readTimeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeoutSeconds,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($encodedPayload !== null) {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
            $options[CURLOPT_POSTFIELDS] = $encodedPayload;
        }

        curl_setopt_array($curl, $options);

        $rawResponse = curl_exec($curl);
        if ($rawResponse === false) {
            $curlError = curl_error($curl);
            curl_close($curl);
            return [
                'status' => 0,
                'raw_response' => '',
                'network_error' => $curlError !== '' ? $curlError : 'Unknown cURL network error',
            ];
        }

        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return [
            'status' => $statusCode,
            'raw_response' => $rawResponse,
            'network_error' => null,
        ];
    }

    private function shouldRetryAttempt(
        string $method,
        int $statusCode,
        ?string $networkError,
        int $attempts,
        int $maxAttempts
    ): bool {
        if (!$this->isIdempotentMethod($method) || $attempts >= $maxAttempts) {
            return false;
        }

        if ($networkError !== null) {
            return true;
        }

        return in_array($statusCode, $this->retryableStatusCodes, true);
    }

    private function isIdempotentMethod(string $method): bool
    {
        return in_array(strtoupper($method), ['GET', 'HEAD', 'PUT', 'DELETE', 'OPTIONS'], true);
    }

    private function backoffDelayMs(int $attempts): int
    {
        if ($this->retryDelayMs <= 0) {
            return 0;
        }

        $multiplier = 2 ** max(0, $attempts - 1);
        return $this->retryDelayMs * $multiplier;
    }

    private function sleepBeforeRetry(int $delayMs): void
    {
        if ($delayMs <= 0) {
            return;
        }

        usleep($delayMs * 1000);
    }

    /**
     * @return array<int, int>
     */
    private static function parseStatusCodeList(string $value): array
    {
        $parts = array_map('trim', explode(',', $value));
        $result = [];

        foreach ($parts as $part) {
            if ($part === '' || !is_numeric($part)) {
                continue;
            }

            $statusCode = (int) $part;
            if ($statusCode < 100 || $statusCode > 599) {
                continue;
            }

            $result[] = $statusCode;
        }

        return array_values(array_unique($result));
    }

    private function buildUrl(string $path, array $query): string
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');

        if ($query !== []) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }

    private function parseResponseBody(string $rawResponse, int $statusCode): mixed
    {
        if ($statusCode === 204 || trim($rawResponse) === '') {
            return null;
        }

        $decoded = json_decode($rawResponse, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return ['raw' => $rawResponse];
    }
}
