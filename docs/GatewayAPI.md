# Gateway APIs

## SMS

### Request
URL: `https://gateway.tld/api/send-sms`  
Method: POST  
```json
{
    "requester": {
        "institution": "SURFnet",
        "identity": "d12cb994-5719-405a-9533-af1beef78ee3"
    },
    "message": {
        "originator": "SURFnet",
        "recipient": "31610101010",
        "body": "DCAF83"
    }
}
```
### Responses  
On *Success* `200 OK`  
```json
{
    "status": "OK"
}
```
MessageBird delivery status is not available for lib/bundle consumers

On *Invalid Request* `400 BAD REQUEST`
```json
{
    "errors": [
        "Invalid phonenumber format"
    ]
}
```

On *Failure* `502 Bad Gateway`  
```json
{
    "errors": [
        "Not enough balance (25)"
    ]
}
```
Messagebird error


## Yubikey

### Request
URL: `https://gateway.tld/api/verify-yubikey`  
Method: POST  
```json
{
    "requester": {
        "institution":"institution",
        "identity":"UUID"
    },
    "otp": { 
        "value": "ccccccbtbhnhgvjjickhkndrllvfcerkrcdttbtigduc"
    }
}
```
### Responses  
On *Success* `200 OK`  
```json
{
    "status": "OK"
}
```

On *Invalid Request* `400 BAD REQUEST`
```json
```

On *Failure* `502 Bad Gateway`  
```json
```



## U2F - Create register request

### Request
URL: `https://gateway.tld/api/u2f/create-register-request`  
Method: POST  
```json
{
    "requester": {
        "institution":"institution",
        "identity":"UUID"
    }
}
```
### Responses  
On *Success* `201 Created`  
```json
{
    "version": "...",
    "challenge": "WYD_Z...",
    "app_id": "https://..."
}
```

On *Internal Server Error* `500 Internal Server Error`
```json
{
    "errors": [
        "..."
    ]
} 
```


## U2F - Register

### Request
URL: `https://gateway.tld/api/u2f/register`  
Method: POST  
```json
{
    "requester": {
        "institution":"institution",
        "identity":"UUID"
    },
    "registration": {
        "request": { 
            "version": "U2F_V2",
            "challenge": "TMH6q...",
            "app_id": "https://app-id.tld"
        },
        "response": {
            "error_code": 0,
            "registration_data": "BQQif...",
            "client_data": "eyJ0e..."
        }
    }
}
```
### Responses  
On *Success* `201 Created`  
```json
{
    "status": "SUCCESS",
    "key_handle": "WYD_Z..."
}
```

On *Failure* `400 Bad Request`
```json
{
    "status": "UNTRUSTED_DEVICE"
}
```
Possible status codes:
- SUCCESS -- Registration was a success.
- UNMATCHED_REGISTRATION_CHALLENGE -- The response challenge did not match the request challenge.
- RESPONSE_NOT_SIGNED_BY_DEVICE -- The response was signed by another party than the device, indicating it was tampered with.
- UNTRUSTED_DEVICE -- The device has not been manufactured by a trusted party.
- PUBLIC_KEY_DECODING_FAILED -- The decoding of the device's public key failed.
- APP_ID_MISMATCH -- A message's AppID didn't match the server's
- DEVICE_ERROR – The device reported an error

On *Internal Server Error* `502 Bad Gateway`  
```json
```


## Create sign request

### Request
URL: `https://gateway.tld/api/u2f/create-sign-request`  
Method: POST  
```json
    "requester": {
        "institution":"institution",
        "identity":"UUID"
    },
    "key_handle": {
        "value": "WYD_z..."
    }
}
```
### Responses  
On *Success* `201 Created`  
```json
{
    "version": "...",
    "challenge": "WYD_Z...",
    "app_id": "https://...",
    "key_handle": "WYD_z"
}
```

On *Failure* `400 Bad Request`
```json
{
    "status": "UNKNOWN_KEY_HANDLE"
}
```

- UNKNOWN_KEY_HANDLE -- No registration with the given key handle is known.

On *Internal Server Error* `500 Internal Server Error`  
```json
{
    "errors": [
        "..."
    ]
} 
```


## Verify authentication

### Request
URL: `https://gateway.tld/api/u2f/verify-authentication`  
Method: POST  
```json
{
    "requester": {
        "institution":"institution",
        "identity":"UUID"
    },
    "authentication": {
        "request": { 
            "version": "U2F_V2",
            "challenge": "TMH6q...",
            "app_id": "https://app-id.tld",
            "key_handle": "WYD_Z..."
        },
        "response": {
            "error_code": 0,
            "key_handle": "WYD_Z...",
            "signature_data": "BQQif...",
            "client_data": "eyJ0e..."
        }
    }
}
```
### Responses  
On *Success* `200 OK`  
```json
{
    "status": "SUCCESS"
}
```

On *Failure* `400 Bad Request`  
```json
{
    "status": "REQUEST_RESPONSE_MISMATCH"
}
```
Possible status codes:
- SUCCESS -- Registration was a success.
- DEVICE_ERROR – Device responded with an error.
- UNKNOWN_KEY_HANDLE -- No registration with the given key handle is known.
- REQUEST_RESPONSE_MISMATCH -- The response challenge did not match the request challenge.
- RESPONSE_NOT_SIGNED_BY_DEVICE -- The response was signed by another party than the device, indicating it was tampered with.
- PUBLIC_KEY_DECODING_FAILED -- The decoding of the device's public key failed.
- APP_ID_MISMATCH -- A message's AppID didn't match the server's


On *Internal Server Erro* `500 Internal Server Error`
```json
{
    "errors": [
        "..."
    ]
} 
```

## Revoke registration

### Request
URL: `https://gateway.tld/api/u2f/revoke-registration`  
Method: POST  
```json
{
    "requester": {
        "institution":"institution",
        "identity":"UUID"
    },
    "key_handle": {
        "value": "WYD_Z..."
    }
}
```
### Responses  
On *Success* `200 OK`  
```json
{
    "status": "SUCCESS"
}
```

On *Failure* `400 Bad Request`  
```json
```
Possible status codes:
- SUCCESS -- Registration was a success.
- UNKNOWN_KEY_HANDLE -- No registration with the given key handle is known.


On *Internal Server Error* `500 Internal Server Error`  
```json
{
    "errors": [
        "..."
    ]
}
```