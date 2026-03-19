<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\ApiException;
use App\Support\Env;

final class JwtService
{
    private string $privateKeyDirectory;
    private int $defaultTtlSeconds;

    public function __construct(?string $privateKeyDirectory = null, ?int $defaultTtlSeconds = null)
    {
        $configuredDir = $privateKeyDirectory ?? Env::get('ASC_PRIVATE_KEY_DIR', 'AuthKey');
        $this->privateKeyDirectory = $this->resolveDirectory($configuredDir);
        $this->defaultTtlSeconds = $defaultTtlSeconds ?? Env::int('ASC_TOKEN_TTL_SECONDS', 1200);
    }

    public function generate(string $issuerId, string $keyId, ?int $ttlSeconds = null): array
    {
        $issuerId = trim($issuerId);
        $keyId = trim($keyId);

        if ($issuerId === '') {
            throw new ApiException(422, 'validation_error', 'Field "iss" is required.');
        }

        if ($keyId === '') {
            throw new ApiException(422, 'validation_error', 'Field "kid" is required.');
        }

        Validation::requireIdentifier($keyId, 'kid');

        $ttl = $ttlSeconds ?? $this->defaultTtlSeconds;
        if ($ttl < 60 || $ttl > 1200) {
            throw new ApiException(422, 'validation_error', 'TTL must be between 60 and 1200 seconds.');
        }

        $privateKey = $this->loadPrivateKey($keyId);

        $issuedAt = time();
        $expiration = $issuedAt + $ttl;

        $header = [
            'alg' => 'ES256',
            'kid' => $keyId,
            'typ' => 'JWT',
        ];

        $payload = [
            'iss' => $issuerId,
            'exp' => $expiration,
            'aud' => 'appstoreconnect-v1',
        ];

        try {
            $token = JwtSigner::sign($payload, $header, $privateKey);
        } catch (\Throwable $throwable) {
            throw new ApiException(
                500,
                'token_sign_error',
                'Failed to sign App Store Connect JWT token.',
                null,
                $throwable
            );
        }

        return [
            'token' => $token,
            'issued_at' => $issuedAt,
            'expiration' => $expiration,
            'ttl_seconds' => $ttl,
        ];
    }

    private function resolveDirectory(string $configuredPath): string
    {
        $configuredPath = trim($configuredPath);
        if ($configuredPath === '') {
            $configuredPath = 'AuthKey';
        }

        if (str_starts_with($configuredPath, '/')) {
            return rtrim($configuredPath, '/');
        }

        return rtrim(dirname(__DIR__, 2) . '/' . ltrim($configuredPath, '/'), '/');
    }

    private function loadPrivateKey(string $keyId): string
    {
        $path = $this->privateKeyDirectory . '/AuthKey_' . $keyId . '.p8';

        if (!is_file($path)) {
            throw new ApiException(
                404,
                'private_key_not_found',
                'Private key file not found for provided "kid".',
                ['path' => $path]
            );
        }

        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            throw new ApiException(500, 'private_key_read_error', 'Failed to read private key file.');
        }

        return $content;
    }
}
