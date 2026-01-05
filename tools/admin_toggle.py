from pathlib import Path
from persistence.storage import load_state, save_state

def main():
    root = Path(__file__).resolve().parents[1]
    data_dir = root / ".k1"
    state = load_state(data_dir)
    state.admin_enabled = not state.admin_enabled
    save_state(data_dir, state)
    print("admin_enabled:", state.admin_enabled)

if __name__ == "__main__":
    main()
