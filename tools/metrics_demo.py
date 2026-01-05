import urllib.request

def main():
    print(
        urllib.request.urlopen("http://127.0.0.1:8080/metrics").read().decode()
    )

if __name__ == "__main__":
    main()
