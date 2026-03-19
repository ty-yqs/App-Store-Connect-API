<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Services\Validation;

final class AppsController extends BaseController
{
    public function index(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $query = $this->listQuery($request);

        return $this->passthrough('GET', '/v1/apps', $token, $query);
    }

    public function show(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $appId = Validation::requireIdentifier((string) ($params['id'] ?? ''), 'id');

        return $this->passthrough('GET', '/v1/apps/' . $appId, $token);
    }

    public function appStoreVersions(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $appId = Validation::requireIdentifier((string) ($params['id'] ?? ''), 'id');
        $query = $this->listQuery($request);

        return $this->passthrough('GET', '/v1/apps/' . $appId . '/appStoreVersions', $token, $query);
    }
}
