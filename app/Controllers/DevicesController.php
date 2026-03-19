<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\ApiException;
use App\Http\Request;
use App\Services\Validation;

final class DevicesController extends BaseController
{
    public function index(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $query = $this->listQuery($request);

        return $this->passthrough('GET', '/v1/devices', $token, $query);
    }

    public function store(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);

        $payload = $this->resolvePayload($request, static function (array $body): array {
            $udid = Validation::requireString($body, 'udid', 100);
            $name = Validation::optionalString($body, 'name', 255) ?? $udid;
            $platform = strtoupper(Validation::optionalString($body, 'platform', 20) ?? 'IOS');

            return [
                'data' => [
                    'type' => 'devices',
                    'attributes' => [
                        'name' => $name,
                        'platform' => $platform,
                        'udid' => $udid,
                    ],
                ],
            ];
        });

        return $this->passthrough('POST', '/v1/devices', $token, [], $payload);
    }

    public function show(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $deviceId = Validation::requireIdentifier((string) ($params['id'] ?? ''), 'id');

        return $this->passthrough('GET', '/v1/devices/' . $deviceId, $token);
    }

    public function update(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $deviceId = Validation::requireIdentifier((string) ($params['id'] ?? ''), 'id');

        $payload = $this->resolvePayload($request, static function (array $body) use ($deviceId): array {
            $attributes = [];

            $name = Validation::optionalString($body, 'name', 255);
            if ($name !== null) {
                $attributes['name'] = $name;
            }

            $status = Validation::optionalString($body, 'status', 20);
            if ($status !== null) {
                $attributes['status'] = strtoupper($status);
            }

            if ($attributes === []) {
                throw new ApiException(422, 'validation_error', 'At least one updatable attribute is required.');
            }

            return [
                'data' => [
                    'id' => $deviceId,
                    'type' => 'devices',
                    'attributes' => $attributes,
                ],
            ];
        });

        return $this->passthrough('PATCH', '/v1/devices/' . $deviceId, $token, [], $payload);
    }

    public function destroy(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $deviceId = Validation::requireIdentifier((string) ($params['id'] ?? ''), 'id');

        return $this->passthrough('DELETE', '/v1/devices/' . $deviceId, $token);
    }
}
