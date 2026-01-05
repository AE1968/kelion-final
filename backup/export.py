from pathlib import Path
import zipfile, datetime

def export_full(root: Path, out_dir: Path) -> Path:
    out_dir.mkdir(parents=True, exist_ok=True)
    ts = datetime.datetime.utcnow().strftime("%Y%m%dT%H%M%SZ")
    out = out_dir / f"k1_full_export_{ts}.zip"

    with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED) as z:
        for p in root.rglob("*"):
            if p.name.endswith(".zip") or "__pycache__" in p.parts:
                continue
            if p.is_file():
                z.write(p, arcname=p.relative_to(root))
    return out
