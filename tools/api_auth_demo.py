import json, urllib.request

def main():
    headers = {
        "Content-Type": "application/json",
        "X-API-Token": "dev-token",
    }
    req = urllib.request.Request(
        "http://127.0.0.1:8080/kv",
        data=json.dumps({"key": "secure", "value": "ok"}).encode(),
        headers=headers,
        method="POST",
    )
    urllib.request.urlopen(req).read()
    print(
        urllib.request.urlopen(
            urllib.request.Request(
                "http://127.0.0.1:8080/kv?key=secure", headers=headers
            )
        ).read().decode()
    )

if __name__ == "__main__":
    main()
