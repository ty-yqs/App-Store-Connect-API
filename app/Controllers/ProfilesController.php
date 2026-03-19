<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\ApiException;
use App\Http\Request;
use App\Services\Validation;

final class ProfilesController extends BaseController
{
    public function index(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $query = $this->listQuery($request);

        return $this->passthrough('GET', '/v1/profiles', $token, $query);
    }

    public function store(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);

        $payload = $this->resolvePayload($request, static function (array $body): array {
            $name = Validation::requireString($body, 'name', 255);
            $profileType = Validation::requireString($body, 'profileType', 100);
            $bundleId = Validation::requireIdentifier((string) ($body['bundleId'] ?? ''), 'bundleId');

            $certificateIds = $body['certificateIds'] ?? null;
            $certificates = self::buildRelationshipList($certificateIds, 'certificates', 'certificateIds');

            $relationships = [
                'bundleId' => [
                    'data' => [
                        'type' => 'bundleIds',
                        'id' => $bundleId,
                    ],
                ],
                'certificates' => [
                    'data' => $certificates,
                ],
            ];

            if (array_key_exists('deviceIds', $body)) {
                $relationships['devices'] = [
                    'data' => self::buildRelationshipList($body['deviceIds'], 'devices', 'deviceIds'),
                ];
            }

            return [
                'data' => [
                    'type' => 'profiles',
                    'attributes' => [
                        'name' => $name,
                        'profileType' => $profileType,
                    ],
                    'relationships' => $relationships,
                ],
            ];
        });

        return $this->passthrough('POST', '/v1/profiles', $token, [], $payload);
    }

    public function show(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $profileId = Validation::requireIdentifier((string) ($params['id'] ?? ''), 'id');

        return $this->passthrough('GET', '/v1/profiles/' . $profileId, $token);
    }

    public function destroy(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $profileId = Validation::requireIdentifier((string) ($params['id'] ?? ''), 'id');

        return $this->passthrough('DELETE', '/v1/profiles/' . $profileId, $token);
    }

    private static function buildRelationshipList(mixed $source, string $type, string $field): array
    {
        if (!is_array($source) || $source === []) {
            throw new ApiException(
                422,
                'validation_error',
                sprintf('Field "%s" must be a non-empty array.', $field),
                ['field' => $field]
            );
        }

        $items = [];
        foreach ($source as $index => $item) {
            if (!is_string($item) && !is_int($item)) {
                throw new ApiException(
                    422,
                    'validation_error',
                    sprintf('Field "%s[%d]" must be a string or integer id.', $field, $index)
                );
            }

            $items[] = [
                'type' => $type,
                'id' => Validation::requireIdentifier((string) $item, $field . '[' . $index . ']'),
            ];
        }

        return $items;
    }
}
