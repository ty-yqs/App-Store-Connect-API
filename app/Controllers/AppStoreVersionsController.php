<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\ApiException;
use App\Http\Request;
use App\Services\Validation;

final class AppStoreVersionsController extends BaseController
{
    public function listLocalizations(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $versionId = Validation::requireIdentifier((string) ($params['id'] ?? ''), 'id');
        $query = $this->listQuery($request);

        return $this->passthrough('GET', '/v1/appStoreVersions/' . $versionId . '/appStoreVersionLocalizations', $token, $query);
    }

    public function showLocalization(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $localizationId = Validation::requireIdentifier((string) ($params['id'] ?? ''), 'id');

        return $this->passthrough('GET', '/v1/appStoreVersionLocalizations/' . $localizationId, $token);
    }

    public function createLocalization(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);

        $payload = $this->resolvePayload($request, static function (array $body): array {
            $appStoreVersionId = Validation::requireIdentifier((string) ($body['appStoreVersionId'] ?? ''), 'appStoreVersionId');
            $locale = Validation::requireString($body, 'locale', 20);

            $attributes = ['locale' => $locale];

            $optionalFields = [
                'description',
                'keywords',
                'marketingUrl',
                'promotionalText',
                'supportUrl',
                'whatsNew',
            ];

            foreach ($optionalFields as $field) {
                $value = Validation::optionalString($body, $field, 4000);
                if ($value !== null) {
                    $attributes[$field] = $value;
                }
            }

            return [
                'data' => [
                    'type' => 'appStoreVersionLocalizations',
                    'attributes' => $attributes,
                    'relationships' => [
                        'appStoreVersion' => [
                            'data' => [
                                'type' => 'appStoreVersions',
                                'id' => $appStoreVersionId,
                            ],
                        ],
                    ],
                ],
            ];
        });

        return $this->passthrough('POST', '/v1/appStoreVersionLocalizations', $token, [], $payload);
    }

    public function updateLocalization(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $localizationId = Validation::requireIdentifier((string) ($params['id'] ?? ''), 'id');

        $payload = $this->resolvePayload($request, static function (array $body) use ($localizationId): array {
            $attributes = [];

            $updatableFields = [
                'description',
                'keywords',
                'marketingUrl',
                'promotionalText',
                'supportUrl',
                'whatsNew',
            ];

            foreach ($updatableFields as $field) {
                $value = Validation::optionalString($body, $field, 4000);
                if ($value !== null) {
                    $attributes[$field] = $value;
                }
            }

            if ($attributes === []) {
                throw new ApiException(422, 'validation_error', 'At least one localization attribute is required.');
            }

            return [
                'data' => [
                    'id' => $localizationId,
                    'type' => 'appStoreVersionLocalizations',
                    'attributes' => $attributes,
                ],
            ];
        });

        return $this->passthrough('PATCH', '/v1/appStoreVersionLocalizations/' . $localizationId, $token, [], $payload);
    }
}
