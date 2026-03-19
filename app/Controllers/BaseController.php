<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\ApiException;
use App\Http\Request;
use App\Services\AppleApiClient;
use App\Services\Validation;

abstract class BaseController
{
    public function __construct(protected AppleApiClient $appleClient)
    {
    }

    protected function requireBearerToken(Request $request): string
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            throw new ApiException(
                401,
                'unauthorized',
                'Authorization header with Bearer token is required.'
            );
        }

        return $token;
    }

    protected function listQuery(Request $request): array
    {
        return Validation::normalizeListQuery($request->queryAll());
    }

    protected function passthrough(
        string $method,
        string $path,
        string $token,
        array $query = [],
        ?array $payload = null
    ): array {
        $response = $this->appleClient->request($method, $path, $token, $query, $payload);

        return [
            'status' => $response['status'],
            'data' => $response['data'],
        ];
    }

    protected function resolvePayload(Request $request, ?callable $builder = null): array
    {
        $body = $request->bodyAll();

        if (isset($body['data']) && is_array($body['data'])) {
            return $body;
        }

        if ($builder !== null) {
            $built = $builder($body);
            if (isset($built['data']) && is_array($built['data'])) {
                return $built;
            }
        }

        throw new ApiException(
            422,
            'validation_error',
            'Request body must include a valid "data" object.'
        );
    }
}
