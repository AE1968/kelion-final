import os

def get(key: str, default=None):
    return os.getenv(key, default)

def require(key: str):
    val = os.getenv(key)
    if val is None:
        raise RuntimeError(f"Missing required env var: {key}")
    return val
