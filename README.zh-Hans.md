## App Store Connect API 使用说明
### 上传AuthKey文件
- 登录 [App Store Conect](https://appstoreconnect.apple.com)
- 进入 [用户和访问](https://appstoreconnect.apple.com/access/users)
- 点击菜单栏密钥，选择密钥类型为`App Store Connect API`
- 点击`+`
- 名称随便填写，访问一栏选取`开发者`
- 确认无误后点击创建按钮
- 点击刚创建的证书对应的最右边`下载API密钥`
- 弹出的窗口中点击`下载`，妥善保管下载的文件
- 上传P8文件到AuthKey文件夹(不要修改文件名)

### 获取Token
- 请求地址: /v1/GetToken
- 请求方式: GET
- 请求参数:

| 参数 | 值        |
|------|-----------|
| iss  | Issuer ID |
| kid  | 密钥 ID   |

- 返回格式: application/json
- 返回码:

| 返回码 | 类型           | 解释                                     | 格式             |
|--------|----------------|------------------------------------------|------------------|
| 201    | TokenResponse | Created.                                 | application/json |
| 409    | ErrorResponse  | The provided resource data is not valid. | application/json |

- 返回样例:

```
{
    "status":"200",
    "expiration":xxx,
    "token":"xxx.xxx.xxx"
}
```
### 注册设备
- 请求地址: /v1/RegisterNewDevice
- 请求方式: GET
- 请求参数:

| 参数  | 值             |
|-------|----------------|
| token | token          |
| udid  | 待注册设备UDID |

- 返回格式: application/json
- 返回码:

| 返回码 | 类型           | 解释                                     | 格式             |
|--------|----------------|------------------------------------------|------------------|
| 201    | DeviceResponse | Created.                                 | application/json |
| 400    | ErrorResponse  | An error occurred with your request.     | application/json |
| 403    | ErrorResponse  | Request not authorized.                  | application/json |
| 409    | ErrorResponse  | The provided resource data is not valid. | application/json |

- 返回样例:

```
{
    "data":{
        "type":"devices",
        "id":"xxx",
        "attributes":{
            "addedDate":"xxxx-xx-xxTxx:xx:xx.xxxxxxx",
            "name":"xxx",
            "deviceClass":"IPHONE",
            "model":"iPhone X",
            "udid":"xxx",
            "platform":"IOS",
            "status":"ENABLED"
        },
        "links":{
            "self":"https://api.appstoreconnect.apple.com/v1/devices/xxx"
        }
    },
    "links":{
        "self":"https://api.appstoreconnect.apple.com/v1/devices"
    }
}
```

### 列出设备
- 请求地址: /v1/ListDevices
- 请求方式: GET
- 请求参数:

| 参数  | 值             |
|-------|----------------|
| token | token          |

- 返回格式: application/json
- 返回码:

| 返回码 | 类型           | 解释                                     | 格式             |
|--------|----------------|------------------------------------------|------------------|
| 200    | DeviceResponse | OK.                                      | application/json |
| 400    | ErrorResponse  | An error occurred with your request.     | application/json |
| 403    | ErrorResponse  | Request not authorized.                  | application/json |

- 返回样例:

```
{
    "data":[
        {
            "type":"devices",
            "id":"xxxxxx",
            "attributes":{
                "udid":"xxxxxx"
            },
            "links":{
                "self":"https://api.appstoreconnect.apple.com/v1/devices/xxxxxx"
            }
        }
    ],
    "links":{
        "self":"https://api.appstoreconnect.apple.com/v1/devices?fields%5Bdevices%5D=udid&limit=200"
    },
    "meta":{
        "paging":{
            "total":1,
            "limit":200
        }
    }
}
```

### 注册BundleID
- 请求地址: /v1/RegisterNewBundleID
- 请求方式: GET
- 请求参数:

| 参数  | 值               |
|-------|------------------|
| token | token            |
| bid   | BundleID的标识符 |
| name  | BundleID的名字   |

- 返回格式: application/json
- 返回码:

| 返回码 | 类型             | 解释                                     | 格式             |
|--------|------------------|------------------------------------------|------------------|
| 201    | BundleIdResponse | Created.                                 | application/json |
| 400    | ErrorResponse    | An error occurred with your request.     | application/json |
| 403    | ErrorResponse    | Request not authorized.                  | application/json |
| 409    | ErrorResponse    | The provided resource data is not valid. | application/json |

- 返回样例:

```
{
    "data":{
        "type":"bundleIds",
        "id":"xxxxxx",
        "attributes":{
            "name":"testbundleid",
            "identifier":"xxx.xxx.xxx",
            "platform":"UNIVERSAL",
            "seedId":"xxxxxx"
        },
        "relationships":{
            "bundleIdCapabilities":{
                "meta":{
                    "paging":{
                        "total":0,
                        "limit":xxx
                    }
                },
                "data":[
                    {
                        "type":"bundleIdCapabilities",
                        "id":"xxxxxx_GAME_CENTER"
                    },
                    {
                        "type":"bundleIdCapabilities",
                        "id":"xxxxxx_IN_APP_PURCHASE"
                    }
                ],
                "links":{
                    "self":"https://api.appstoreconnect.apple.com/v1/bundleIds/xxxxxx/relationships/bundleIdCapabilities",
                    "related":"https://api.appstoreconnect.apple.com/v1/bundleIds/xxxxxx/bundleIdCapabilities"
                }
            },
            "profiles":{
                "meta":{
                    "paging":{
                        "total":0,
                        "limit":xxx
                    }
                },
                "data":[

                ],
                "links":{
                    "self":"https://api.appstoreconnect.apple.com/v1/bundleIds/xxxxxx/relationships/profiles",
                    "related":"https://api.appstoreconnect.apple.com/v1/bundleIds/xxxxxx/profiles"
                }
            }
        },
        "links":{
            "self":"https://api.appstoreconnect.apple.com/v1/bundleIds/xxxxxx"
        }
    },
    "links":{
        "self":"https://api.appstoreconnect.apple.com/v1/bundleIds"
    }
}
```

### 列出BundleID
- 请求地址: /v1/ListBundleIDs
- 请求方式: GET
- 请求参数:

| 参数  | 值               |
|-------|------------------|
| token | token            |

- 返回格式: application/json
- 返回码:

| 返回码 | 类型             | 解释                                     | 格式             |
|--------|------------------|------------------------------------------|------------------|
| 200    | BundleIdResponse | OK.                                 | application/json |
| 400    | ErrorResponse    | An error occurred with your request.     | application/json |
| 403    | ErrorResponse    | Request not authorized.                  | application/json |

- 返回样例:

```
{
    "data":[
        {
            "type":"bundleIds",
            "id":"ZHR8XPJ5J4",
            "attributes":{
                "identifier":"com.ty.OldOS"
            },
            "links":{
                "self":"https://api.appstoreconnect.apple.com/v1/bundleIds/ZHR8XPJ5J4"
            }
        }
    ],
    "links":{
        "self":"https://api.appstoreconnect.apple.com/v1/bundleIds?fields%5BbundleIds%5D=identifier&limit=200"
    },
    "meta":{
        "paging":{
            "total":1,
            "limit":200
        }
    }
}
```

### 列出Apps
- 请求地址: /v1/ListApps
- 请求方式: GET
- 请求参数:

| 参数  | 值               |
|-------|------------------|
| token | token            |

- 返回格式: application/json
- 返回码:

| 返回码 | 类型             | 解释                                     | 格式             |
|--------|------------------|------------------------------------------|------------------|
| 200    | BundleIdResponse | OK.                                 | application/json |
| 400    | ErrorResponse    | An error occurred with your request.     | application/json |
| 403    | ErrorResponse    | Request not authorized.                  | application/json |

- 返回样例:

```
{
    "data":[
        {
            "type":"apps",
            "id":"xxx",
            "attributes":{
                "name":"xxx",
                "bundleId":"com.xx.xxx",
                "sku":"com.xx.xxx",
                "primaryLocale":"zh-Hans",
                "isOrEverWasMadeForKids":false,
                "availableInNewTerritories":false,
                "contentRightsDeclaration":null
            },
            "relationships":{
                "ciProduct":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx/relationships/ciProduct",
                        "related":"https://api.appstoreconnect.apple.com/v1/apps/xxx/ciProduct"
                    }
                },
                "betaTesters":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx/relationships/betaTesters"
                    }
                },
                "betaGroups":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx/relationships/betaGroups",
                        "related":"https://api.appstoreconnect.apple.com/v1/apps/xxx/betaGroups"
                    }
                },
                "appStoreVersions":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx/relationships/appStoreVersions",
                        "related":"https://api.appstoreconnect.apple.com/v1/apps/xxx/appStoreVersions"
                    }
                },
                "preReleaseVersions":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx/relationships/preReleaseVersions",
                        "related":"https://api.appstoreconnect.apple.com/v1/apps/xxx/preReleaseVersions"
                    }
                },
                "betaAppLocalizations":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx/relationships/betaAppLocalizations",
                        "related":"https://api.appstoreconnect.apple.com/v1/apps/xxx/betaAppLocalizations"
                    }
                },
                "builds":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx/relationships/builds",
                        "related":"https://api.appstoreconnect.apple.com/v1/apps/xxx/builds"
                    }
                },
                "betaLicenseAgreement":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx/relationships/betaLicenseAgreement",
                        "related":"https://api.appstoreconnect.apple.com/v1/apps/xxx/betaLicenseAgreement"
                    }
                },
                "betaAppReviewDetail":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx/relationships/betaAppReviewDetail",
                        "related":"https://api.appstoreconnect.apple.com/v1/apps/xxx/betaAppReviewDetail"
                    }
                },
                "appInfos":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx/relationships/appInfos",
                        "related":"https://api.appstoreconnect.apple.com/v1/apps/xxx/appInfos"
                    }
                },
                "endUserLicenseAgreement":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx/relationships/endUserLicenseAgreement",
                        "related":"https://api.appstoreconnect.apple.com/v1/apps/xxx/endUserLicenseAgreement"
                    }
                },
                "preOrder":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx/relationships/preOrder",
                        "related":"https://api.appstoreconnect.apple.com/v1/apps/xxx/preOrder"
                    }
                },
                "prices":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx/relationships/prices",
                        "related":"https://api.appstoreconnect.apple.com/v1/apps/xxx/prices"
                    }
                },
                "availableTerritories":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx/relationships/availableTerritories",
                        "related":"https://api.appstoreconnect.apple.com/v1/apps/xxx/availableTerritories"
                    }
                },
                "inAppPurchases":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx/relationships/inAppPurchases",
                        "related":"https://api.appstoreconnect.apple.com/v1/apps/xxx/inAppPurchases"
                    }
                },
                "gameCenterEnabledVersions":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx/relationships/gameCenterEnabledVersions",
                        "related":"https://api.appstoreconnect.apple.com/v1/apps/xxx/gameCenterEnabledVersions"
                    }
                },
                "perfPowerMetrics":{
                    "links":{
                        "related":"https://api.appstoreconnect.apple.com/v1/apps/xxx/perfPowerMetrics"
                    }
                }
            },
            "links":{
                "self":"https://api.appstoreconnect.apple.com/v1/apps/xxx"
            }
        }
    ],
    "links":{
        "self":"https://api.appstoreconnect.apple.com/v1/apps"
    },
    "meta":{
        "paging":{
            "total":1,
            "limit":50
        }
    }
}
```

### 列出证书
- 请求地址: /v1/ListCertifications
- 请求方式: GET
- 请求参数:

| 参数  | 值             |
|-------|----------------|
| token | token          |

- 返回格式: application/json
- 返回码:

| 返回码 | 类型                 | 解释                                     | 格式             |
|--------|----------------------|------------------------------------------|------------------|
| 200    | CertificatesResponse | OK.                                      | application/json |
| 400    | ErrorResponse        | An error occurred with your request.     | application/json |
| 403    | ErrorResponse        | Request not authorized.                  | application/json |

- 返回样例:

```
{
    "data":[
        {
            "type":"certificates",
            "id":"xxx",
            "attributes":{
                "serialNumber":"xxx",
                "certificateContent":"xxx",
                "displayName":"xxx",
                "name":"Apple Development: xxx",
                "csrContent":null,
                "platform":null,
                "expirationDate":"xxxx-xx-xxTxx:xx:xx.xxx+xxxx",
                "certificateType":"DEVELOPMENT"
            },
            "relationships":{
                "passTypeId":{
                    "links":{
                        "self":"https://api.appstoreconnect.apple.com/v1/certificates/xxx/relationships/passTypeId",
                        "related":"https://api.appstoreconnect.apple.com/v1/certificates/xxx/passTypeId"
                    }
                }
            },
            "links":{
                "self":"https://api.appstoreconnect.apple.com/v1/certificates/xxx"
            }
        }
    ],
    "links":{
        "self":"https://api.appstoreconnect.apple.com/v1/certificates?limit=200"
    },
    "meta":{
        "paging":{
            "total":1,
            "limit":200
        }
    }
}
```
