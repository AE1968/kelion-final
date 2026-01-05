from config.env import get

def token():
    return get("K1_API_TOKEN", "dev-token")

def check(headers) -> bool:
    return headers.get("X-API-Token") == token()
