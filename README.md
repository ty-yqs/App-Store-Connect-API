## App Store Connect API

### Upload AuthKey

- Upload P8 file into `/AuthKey`  **(NOT TO EDIT THE FILE NAME)**

### Get Token

- URL: /v1/GetToken
- Method: GET
- Parameter:

| Parameter | Description |
| --------- | ----------- |
| iss       | Issuer ID   |
| kid       | Key ID      |

- Return Format: application/json
- Return Code:

| Return Code | Type          | Description                              | *Type            |
| ----------- | ------------- | ---------------------------------------- | ---------------- |
| 201         | TokenResponse | Created.                                 | application/json |
| 409         | ErrorResponse | The provided resource data is not valid. | application/json |

- Return Example:

```json
{
    "status":"200",
    "expiration":xxx,
    "token":"xxx.xxx.xxx"
}
```

### 注册设备

- URL: /v1/RegisterNewDevice
- Method: GET
- Parameter:

| Parameter | Description |
| --------- | ----------- |
| token     | token       |
| udid      | UDID        |

- Return Format: application/json
- Return Code:

| Return Code | Type           | Description                              | *Type            |
| ----------- | -------------- | ---------------------------------------- | ---------------- |
| 201         | DeviceResponse | Created.                                 | application/json |
| 400         | ErrorResponse  | An error occurred with your request.     | application/json |
| 403         | ErrorResponse  | Request not authorized.                  | application/json |
| 409         | ErrorResponse  | The provided resource data is not valid. | application/json |

- Return Example:

```json
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

- URL: /v1/ListDevices
- Method: GET
- Parameter:

| Parameter | Description |
| --------- | ----------- |
| token     | token       |

- Return Format: application/json
- Return Code:

| Return Code | Type           | Description                          | *Type            |
| ----------- | -------------- | ------------------------------------ | ---------------- |
| 200         | DeviceResponse | OK.                                  | application/json |
| 400         | ErrorResponse  | An error occurred with your request. | application/json |
| 403         | ErrorResponse  | Request not authorized.              | application/json |

- Return Example:

```json
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

- URL: /v1/RegisterNewBundleID
- Method: GET
- Parameter:

| Parameter | Description      |
| --------- | ---------------- |
| token     | token            |
| bid       | BundleID         |
| name      | Name of BundleID |

- Return Format: application/json
- Return Code:

| Return Code | Type             | Description                              | *Type            |
| ----------- | ---------------- | ---------------------------------------- | ---------------- |
| 201         | BundleIdResponse | Created.                                 | application/json |
| 400         | ErrorResponse    | An error occurred with your request.     | application/json |
| 403         | ErrorResponse    | Request not authorized.                  | application/json |
| 409         | ErrorResponse    | The provided resource data is not valid. | application/json |

- Return Example:

```json
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

- URL: /v1/ListBundleIDs
- Method: GET
- Parameter:

| Parameter | Description |
| --------- | ----------- |
| token     | token       |

- Return Format: application/json
- Return Code:

| Return Code | Type             | Description                          | *Type            |
| ----------- | ---------------- | ------------------------------------ | ---------------- |
| 200         | BundleIdResponse | OK.                                  | application/json |
| 400         | ErrorResponse    | An error occurred with your request. | application/json |
| 403         | ErrorResponse    | Request not authorized.              | application/json |

- Return Example:

```json
{
    "data":[
        {
            "type":"bundleIds",
            "id":"xxx",
            "attributes":{
                "identifier":"xxx.xxx.xxx"
            },
            "links":{
                "self":"https://api.appstoreconnect.apple.com/v1/bundleIds/xxx"
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

- URL: /v1/ListApps
- Method: GET
- Parameter:

| Parameter | Description |
| --------- | ----------- |
| token     | token       |

- Return Format: application/json
- Return Code:

| Return Code | Type             | Description                          | *Type            |
| ----------- | ---------------- | ------------------------------------ | ---------------- |
| 200         | BundleIdResponse | OK.                                  | application/json |
| 400         | ErrorResponse    | An error occurred with your request. | application/json |
| 403         | ErrorResponse    | Request not authorized.              | application/json |

- Return Example:

```json
{
    "data":[
        {
            "type":"apps",
            "id":"xxx",
            "attributes":{
                "name":"xxx",
                "bundleId":"com.xx.xxx",
                "sku":"com.xx.xxx",
                "primaryLocale":"xxx",
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

- URL: /v1/ListCertifications
- Method: GET
- Parameter:

| Parameter | Description |
| --------- | ----------- |
| token     | token       |

- Return Format: application/json
- Return Code:

| Return Code | Type                 | Description                          | *Type            |
| ----------- | -------------------- | ------------------------------------ | ---------------- |
| 200         | CertificatesResponse | OK.                                  | application/json |
| 400         | ErrorResponse        | An error occurred with your request. | application/json |
| 403         | ErrorResponse        | Request not authorized.              | application/json |

- Return Example:

```json
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
