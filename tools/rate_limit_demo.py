import json, urllib.request

def main():
    headers = {
        "Content-Type": "application/json",
        "X-API-Token": "dev-token",
    }
    for i in range(7):
        try:
            req = urllib.request.Request(
                "http://127.0.0.1:8080/kv",
                data=json.dumps({"key": f"k{i}", "value": f"v{i}"}).encode(),
                headers=headers,
                method="POST",
            )
            urllib.request.urlopen(req).read()
            print("OK", i)
        except Exception as e:
            print("BLOCKED", i, e)

if __name__ == "__main__":
    main()
