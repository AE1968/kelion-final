from datetime import datetime
from pathlib import Path

def audit(data_dir: Path, action: str, actor: str = "system") -> None:
    audits = data_dir / "audit"
    audits.mkdir(parents=True, exist_ok=True)
    fp = audits / "audit.log"
    ts = datetime.utcnow().isoformat()
    with fp.open("a", encoding="utf-8") as f:
        f.write(f"[{ts}] actor={actor} action={action}\n")
