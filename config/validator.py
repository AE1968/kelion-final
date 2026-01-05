REQUIRED_KEYS = {
    "app_name": str,
    "default_language": str,
    "data_dir": str,
}

def validate(settings: dict) -> None:
    missing = [k for k in REQUIRED_KEYS if k not in settings]
    if missing:
        raise ValueError(f"Missing config keys: {missing}")
    for k, t in REQUIRED_KEYS.items():
        if not isinstance(settings.get(k), t):
            raise TypeError(f"Config key {k} must be {t.__name__}")
