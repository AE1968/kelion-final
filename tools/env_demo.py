from config.env import get

def main():
    print("K1_API_TOKEN =", get("K1_API_TOKEN", "<not set>"))

if __name__ == "__main__":
    main()
