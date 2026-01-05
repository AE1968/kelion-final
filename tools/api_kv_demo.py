import json, urllib.request

def main():
    # POST
    req = urllib.request.Request(
        "http://127.0.0.1:8080/kv",
        data=json.dumps({"key": "a", "value": "1"}).encode(),
        headers={"Content-Type": "application/json"},
        method="POST",
    )
    urllib.request.urlopen(req).read()

    # GET
    print(
        urllib.request.urlopen("http://127.0.0.1:8080/kv?key=a").read().decode()
    )

if __name__ == "__main__":
    main()
