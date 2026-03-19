<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\ApiException;
use App\Support\Env;

final class AppleApiClient
{
    private string $baseUrl;
    private int $timeoutSeconds;

    public function __construct(?string $baseUrl = null, ?int $timeoutSeconds = null)
    {
        $configuredBaseUrl = $baseUrl ?? Env::get('ASC_BASE_URL', 'https://api.appstoreconnect.apple.com');
        $configuredTimeout = $timeoutSeconds ?? Env::int('ASC_HTTP_TIMEOUT', 30);

        $this->baseUrl = rtrim($configuredBaseUrl, '/');
        $this->timeoutSeconds = $configuredTimeout > 0 ? $configuredTimeout : 30;
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
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => min($this->timeoutSeconds, 10),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($payload !== null) {
            $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encodedPayload === false) {
                curl_close($curl);
                throw new ApiException(500, 'payload_encode_error', 'Failed to encode request payload.');
            }

            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
            $options[CURLOPT_POSTFIELDS] = $encodedPayload;
        }

        curl_setopt_array($curl, $options);

        $rawResponse = curl_exec($curl);
        if ($rawResponse === false) {
            $curlError = curl_error($curl);
            curl_close($curl);
            throw new ApiException(
                502,
                'upstream_network_error',
                'Failed to call App Store Connect API.',
                ['reason' => $curlError]
            );
        }

        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $responseData = $this->parseResponseBody($rawResponse, $statusCode);

        if ($statusCode >= 400) {
            throw new ApiException(
                $statusCode,
                'apple_api_error',
                'App Store Connect API returned an error response.',
                [
                    'method' => strtoupper($method),
                    'path' => '/' . ltrim($path, '/'),
                    'query' => $query,
                    'upstream_response' => $responseData,
                ]
            );
        }

        return [
            'status' => $statusCode,
            'data' => $responseData,
        ];
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
