# App Store Connect API Gateway

一个基于 PHP 的 App Store Connect API REST 网关。

<a href="https://www.star-history.com/?repos=ty-yqs%2FApp-Store-Connect-API&type=date&legend=top-left">
 <picture>
   <source media="(prefers-color-scheme: dark)" srcset="https://api.star-history.com/image?repos=ty-yqs/App-Store-Connect-API&type=date&theme=dark&legend=top-left" />
   <source media="(prefers-color-scheme: light)" srcset="https://api.star-history.com/image?repos=ty-yqs/App-Store-Connect-API&type=date&legend=top-left" />
   <img alt="Star History Chart" src="https://api.star-history.com/image?repos=ty-yqs/App-Store-Connect-API&type=date&legend=top-left" />
 </picture>
</a>

## 当前状态

- 接口风格: 仅 REST
- 鉴权模式: 受保护接口仅支持 Authorization Bearer

## 已实现模块

- Token: 生成 App Store Connect JWT
- Apps: 列表、详情、App Store 版本
- Devices: 列表、创建、详情、更新、删除
- Bundle IDs: 列表、创建、详情、更新、删除
- Certificates: 列表、详情、删除
- Profiles: 列表、创建、详情、删除
- TestFlight: beta groups、beta testers、builds
- App 版本本地化: 列表、创建、详情、更新

## 项目结构

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

## 运行要求

- PHP 8.1+
- PHP 扩展: curl、openssl、json
- Composer (推荐，用于测试)

## HTTP 客户端调优

Apple API 客户端已支持超时分级与幂等重试。

- `ASC_HTTP_CONNECT_TIMEOUT`: 连接超时（秒）
- `ASC_HTTP_READ_TIMEOUT`: 读取超时（秒）
- `ASC_HTTP_MAX_RETRIES`: 最大重试次数，仅对幂等方法生效（GET/HEAD/PUT/DELETE/OPTIONS）
- `ASC_HTTP_RETRY_DELAY_MS`: 指数退避基础间隔（毫秒）
- `ASC_HTTP_RETRYABLE_CODES`: 可重试 HTTP 状态码列表（逗号分隔）

兼容回退说明:

- 若未设置分级超时变量，仍会回退使用 `ASC_HTTP_TIMEOUT`。
- 非幂等方法（`POST`、`PATCH`）不会自动重试。

## 请求日志

请求日志默认开启，按 JSON Lines 记录以下事件:

- 入站请求（inbound）
- 出站响应（outbound）
- 未捕获异常（error）
- 上游 Apple API 尝试与结果（upstream attempt/result）

配置项:

- `LOG_ENABLED`: 开启或关闭全部请求日志
- `LOG_LEVEL`: 最低输出级别（`debug`、`info`、`warn`、`error`）
- `LOG_HTTP_ENABLED`: 开启或关闭上游 HTTP 尝试/结果日志
- `LOG_STDERR_ENABLED`: 是否输出到 stderr
- `LOG_FILEPATH`: 文件输出路径（会自动创建父目录）

安全默认策略:

- 默认会对敏感字段脱敏（如 `authorization`、`token`、`secret`、`password`、`key` 等）
- query/body/header 会先清洗再写入日志
- 超长字符串会被截断，避免日志体积失控

## 快速开始

1. 复制环境变量模板:

```bash
cp .env.example .env
```

2. 放置密钥文件到 AuthKey:

```text
AuthKey/AuthKey_<kid>.p8
```

3. 启动本地服务:

```bash
php -S 127.0.0.1:8080 v1/index.php
```

4. 生成 JWT:

```bash
curl -X POST http://127.0.0.1:8080/v1/token \
  -H 'Content-Type: application/json' \
  -d '{"iss":"<issuer-id>","kid":"<key-id>"}'
```

5. 调用受保护接口:

```bash
curl http://127.0.0.1:8080/v1/apps \
  -H 'Authorization: Bearer <jwt-token>'
```

## 接口列表

### 公共接口

- GET /v1/health
- POST /v1/token

### 受保护接口

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

## 统一响应格式

成功:

```json
{
  "success": true,
  "request_id": "84744b77baefc0c3",
  "data": {}
}
```

失败:

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

- 规范文件: openapi.yaml

## 测试

```bash
composer install
vendor/bin/phpunit
```

如果系统没有全局 composer，可使用项目本地 composer:

```bash
php ./composer install
php ./vendor/bin/phpunit
```

## 迁移映射

| 旧接口 | 新接口 |
| --- | --- |
| GET /v1/GetToken | POST /v1/token |
| GET /v1/ListApps | GET /v1/apps |
| GET /v1/ListBundleIDs | GET /v1/bundleIds |
| GET /v1/ListCertifications | GET /v1/certificates |
| GET /v1/ListDevices | GET /v1/devices |
| GET /v1/RegisterNewDevice | POST /v1/devices |
| GET /v1/RegisterNewBundleID | POST /v1/bundleIds |

鉴权迁移:

- 旧版: query 参数 token
- 新版: Authorization Bearer token
