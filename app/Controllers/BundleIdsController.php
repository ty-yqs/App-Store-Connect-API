<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\ApiException;
use App\Http\Request;
use App\Services\Validation;

final class BundleIdsController extends BaseController
{
    public function index(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $query = $this->listQuery($request);

        return $this->passthrough('GET', '/v1/bundleIds', $token, $query);
    }

    public function store(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);

        $payload = $this->resolvePayload($request, static function (array $body): array {
            $identifier = Validation::requireString($body, 'identifier', 255);
            $name = Validation::requireString($body, 'name', 255);
            $platform = strtoupper(Validation::optionalString($body, 'platform', 20) ?? 'IOS');

            return [
                'data' => [
                    'type' => 'bundleIds',
                    'attributes' => [
                        'identifier' => $identifier,
                        'name' => $name,
                        'platform' => $platform,
                    ],
                ],
            ];
        });

        return $this->passthrough('POST', '/v1/bundleIds', $token, [], $payload);
    }

    public function show(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $bundleId = Validation::requireIdentifier((string) ($params['id'] ?? ''), 'id');

        return $this->passthrough('GET', '/v1/bundleIds/' . $bundleId, $token);
    }

    public function update(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $bundleId = Validation::requireIdentifier((string) ($params['id'] ?? ''), 'id');

        $payload = $this->resolvePayload($request, static function (array $body) use ($bundleId): array {
            $attributes = [];

            $name = Validation::optionalString($body, 'name', 255);
            if ($name !== null) {
                $attributes['name'] = $name;
            }

            $platform = Validation::optionalString($body, 'platform', 20);
            if ($platform !== null) {
                $attributes['platform'] = strtoupper($platform);
            }

            if ($attributes === []) {
                throw new ApiException(422, 'validation_error', 'At least one updatable attribute is required.');
            }

            return [
                'data' => [
                    'id' => $bundleId,
                    'type' => 'bundleIds',
                    'attributes' => $attributes,
                ],
            ];
        });

        return $this->passthrough('PATCH', '/v1/bundleIds/' . $bundleId, $token, [], $payload);
    }

    public function destroy(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $bundleId = Validation::requireIdentifier((string) ($params['id'] ?? ''), 'id');

        return $this->passthrough('DELETE', '/v1/bundleIds/' . $bundleId, $token);
    }
}
