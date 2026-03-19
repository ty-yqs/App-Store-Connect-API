<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Services\JwtService;

final class TokenController
{
    public function __construct(private readonly JwtService $jwtService)
    {
    }

    public function create(Request $request, array $params = []): array
    {
        $body = $request->bodyAll();

        $issuerId = (string) ($body['iss'] ?? '');
        $keyId = (string) ($body['kid'] ?? '');

        $ttl = null;
        if (isset($body['ttl'])) {
            if (is_numeric((string) $body['ttl'])) {
                $ttl = (int) $body['ttl'];
            }
        }

        $data = $this->jwtService->generate($issuerId, $keyId, $ttl);

        return [
            'status' => 201,
            'data' => $data,
        ];
    }
}
