# App Store Connect API Gateway

A lightweight PHP REST gateway for Apple App Store Connect API.

## Current Status

- API style: REST only
- Auth mode: Authorization Bearer only for protected routes

## Implemented Modules

- Token: create JWT for App Store Connect
- Apps: list, details, app store versions
- Devices: list, create, details, update, delete
- Bundle IDs: list, create, details, update, delete
- Certificates: list, details, delete
- Profiles: list, create, details, delete
- TestFlight: beta groups, beta testers, builds
- App Store Version Localizations: list, create, details, update

## Project Structure

```text
app/
  Controllers/
  Http/
  Services/
  Support/
v1/
  index.php
  routes.php
openapi.yaml
```

## Requirements

- PHP 8.1+
- PHP extensions: curl, openssl, json
- Composer (recommended, for tests)

## Quick Start

1. Copy env template:

```bash
cp .env.example .env
```

2. Put your key file in AuthKey:

```text
AuthKey/AuthKey_<kid>.p8
```

3. Start local server:

```bash
php -S 127.0.0.1:8080 v1/index.php
```

4. Create JWT token:

```bash
curl -X POST http://127.0.0.1:8080/v1/token \
  -H 'Content-Type: application/json' \
  -d '{"iss":"<issuer-id>","kid":"<key-id>"}'
```

5. Call protected endpoint:

```bash
curl http://127.0.0.1:8080/v1/apps \
  -H 'Authorization: Bearer <jwt-token>'
```

## API Endpoints

### Public

- GET /v1/health
- POST /v1/token

### Protected

- GET /v1/apps
- GET /v1/apps/{id}
- GET /v1/apps/{id}/appStoreVersions
- GET /v1/devices
- POST /v1/devices
- GET /v1/devices/{id}
- PATCH /v1/devices/{id}
- DELETE /v1/devices/{id}
- GET /v1/bundleIds
- POST /v1/bundleIds
- GET /v1/bundleIds/{id}
- PATCH /v1/bundleIds/{id}
- DELETE /v1/bundleIds/{id}
- GET /v1/certificates
- GET /v1/certificates/{id}
- DELETE /v1/certificates/{id}
- GET /v1/profiles
- POST /v1/profiles
- GET /v1/profiles/{id}
- DELETE /v1/profiles/{id}
- GET /v1/betaGroups
- GET /v1/betaTesters
- GET /v1/builds
- GET /v1/appStoreVersions/{id}/appStoreVersionLocalizations
- POST /v1/appStoreVersionLocalizations
- GET /v1/appStoreVersionLocalizations/{id}
- PATCH /v1/appStoreVersionLocalizations/{id}

## Unified Response Format

Success:

```json
{
  "success": true,
  "request_id": "84744b77baefc0c3",
  "data": {}
}
```

Error:

```json
{
  "success": false,
  "request_id": "c2d188ac8ef6f499",
  "error": {
    "code": "unauthorized",
    "message": "Authorization header with Bearer token is required.",
    "details": null
  }
}
```

## OpenAPI

- Specification file: openapi.yaml

## Testing

```bash
composer install
vendor/bin/phpunit
```

If composer is not available globally, use local composer binary:

```bash
php ./composer install
php ./vendor/bin/phpunit
```

## Migration Mapping

| Legacy endpoint | New endpoint |
| --- | --- |
| GET /v1/GetToken | POST /v1/token |
| GET /v1/ListApps | GET /v1/apps |
| GET /v1/ListBundleIDs | GET /v1/bundleIds |
| GET /v1/ListCertifications | GET /v1/certificates |
| GET /v1/ListDevices | GET /v1/devices |
| GET /v1/RegisterNewDevice | POST /v1/devices |
| GET /v1/RegisterNewBundleID | POST /v1/bundleIds |

Auth migration:

- Legacy: query token parameter
- New: Authorization header with Bearer token
