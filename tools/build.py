from pathlib import Path
import zipfile, datetime

ROOT = Path(__file__).resolve().parents[1]

def main():
    version = "0.2"
    stage = "day2"
    ts = datetime.datetime.utcnow().strftime("%Y%m%d")
    out = ROOT / f"k1_{version}_{stage}_{ts}.zip"

    with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED) as z:
        for p in ROOT.rglob("*"):
            if p.name.endswith(".zip") or p.name == "__pycache__":
                continue
            if p.is_file():
                z.write(p, arcname=p.relative_to(ROOT))
    print(f"Build created: {out}")

if __name__ == "__main__":
    main()
