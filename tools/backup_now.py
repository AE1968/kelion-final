from pathlib import Path
from backup.export import export_full

def main():
    root = Path(__file__).resolve().parents[1]
    out = export_full(root, root / "exports")
    print("Backup created:", out)

if __name__ == "__main__":
    main()
