<?php

declare(strict_types=1);

use App\Controllers\AppsController;
use App\Controllers\AppStoreVersionsController;
use App\Controllers\BundleIdsController;
use App\Controllers\CertificatesController;
use App\Controllers\DevicesController;
use App\Controllers\ProfilesController;
use App\Controllers\TestFlightController;
use App\Controllers\TokenController;
use App\Http\Router;
use App\Services\AppleApiClient;
use App\Services\JwtService;

return static function (Router $router): void {
    $appleApiClient = new AppleApiClient();

    $tokenController = new TokenController(new JwtService());
    $appsController = new AppsController($appleApiClient);
    $bundleIdsController = new BundleIdsController($appleApiClient);
    $devicesController = new DevicesController($appleApiClient);
    $certificatesController = new CertificatesController($appleApiClient);
    $profilesController = new ProfilesController($appleApiClient);
    $testFlightController = new TestFlightController($appleApiClient);
    $appStoreVersionsController = new AppStoreVersionsController($appleApiClient);

    $router->get('/v1/health', static function (): array {
        return [
            'status' => 200,
            'data' => ['status' => 'ok'],
        ];
    });

    $router->post('/v1/token', [$tokenController, 'create']);

    $router->get('/v1/apps', [$appsController, 'index']);
    $router->get('/v1/apps/{id}', [$appsController, 'show']);
    $router->get('/v1/apps/{id}/appStoreVersions', [$appsController, 'appStoreVersions']);

    $router->get('/v1/bundleIds', [$bundleIdsController, 'index']);
    $router->post('/v1/bundleIds', [$bundleIdsController, 'store']);
    $router->get('/v1/bundleIds/{id}', [$bundleIdsController, 'show']);
    $router->patch('/v1/bundleIds/{id}', [$bundleIdsController, 'update']);
    $router->delete('/v1/bundleIds/{id}', [$bundleIdsController, 'destroy']);

    $router->get('/v1/devices', [$devicesController, 'index']);
    $router->post('/v1/devices', [$devicesController, 'store']);
    $router->get('/v1/devices/{id}', [$devicesController, 'show']);
    $router->patch('/v1/devices/{id}', [$devicesController, 'update']);
    $router->delete('/v1/devices/{id}', [$devicesController, 'destroy']);

    $router->get('/v1/certificates', [$certificatesController, 'index']);
    $router->get('/v1/certificates/{id}', [$certificatesController, 'show']);
    $router->delete('/v1/certificates/{id}', [$certificatesController, 'destroy']);

    $router->get('/v1/profiles', [$profilesController, 'index']);
    $router->post('/v1/profiles', [$profilesController, 'store']);
    $router->get('/v1/profiles/{id}', [$profilesController, 'show']);
    $router->delete('/v1/profiles/{id}', [$profilesController, 'destroy']);

    $router->get('/v1/betaGroups', [$testFlightController, 'betaGroups']);
    $router->get('/v1/betaTesters', [$testFlightController, 'betaTesters']);
    $router->get('/v1/builds', [$testFlightController, 'builds']);

    $router->get('/v1/appStoreVersions/{id}/appStoreVersionLocalizations', [$appStoreVersionsController, 'listLocalizations']);
    $router->get('/v1/appStoreVersionLocalizations/{id}', [$appStoreVersionsController, 'showLocalization']);
    $router->post('/v1/appStoreVersionLocalizations', [$appStoreVersionsController, 'createLocalization']);
    $router->patch('/v1/appStoreVersionLocalizations/{id}', [$appStoreVersionsController, 'updateLocalization']);
};
