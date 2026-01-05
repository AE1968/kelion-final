from datetime import datetime
from pathlib import Path

def log(data_dir: Path, message: str) -> None:
    logs = data_dir / "logs"
    logs.mkdir(parents=True, exist_ok=True)
    fp = logs / "k1.log"
    ts = datetime.utcnow().isoformat()
    with fp.open("a", encoding="utf-8") as f:
        f.write(f"[{ts}] {message}\n")
