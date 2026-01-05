from pathlib import Path
from persistence.storage import load_state

def main():
    root = Path(__file__).resolve().parents[1]
    data_dir = root / ".k1"
    state = load_state(data_dir)
    print("OK: state loadable")
    print("Version:", state.version)

if __name__ == "__main__":
    main()
