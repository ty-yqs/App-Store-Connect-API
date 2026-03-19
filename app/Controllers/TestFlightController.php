<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;

final class TestFlightController extends BaseController
{
    public function betaGroups(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $query = $this->listQuery($request);

        return $this->passthrough('GET', '/v1/betaGroups', $token, $query);
    }

    public function betaTesters(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $query = $this->listQuery($request);

        return $this->passthrough('GET', '/v1/betaTesters', $token, $query);
    }

    public function builds(Request $request, array $params = []): array
    {
        $token = $this->requireBearerToken($request);
        $query = $this->listQuery($request);

        return $this->passthrough('GET', '/v1/builds', $token, $query);
    }
}
