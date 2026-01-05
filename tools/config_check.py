from pathlib import Path
from run import load_settings
from config.validator import validate

def main():
    root = Path(__file__).resolve().parents[1]
    settings = load_settings(root)
    validate(settings)
    print("Config OK")

if __name__ == "__main__":
    main()
