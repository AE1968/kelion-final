from __future__ import annotations

import json
from dataclasses import asdict
from datetime import datetime, timezone
from pathlib import Path

from core.state import K1State
from .storage import ensure_dir

def snapshots_dir(data_dir: Path) -> Path:
    return data_dir / "snapshots"

def create_snapshot(data_dir: Path, state: K1State, tag: str = "manual") -> Path:
    """Create an immutable-ish snapshot file (Day 1: JSON copy)."""
    sdir = snapshots_dir(data_dir)
    ensure_dir(sdir)
    ts = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
    safe_tag = "".join(c for c in tag.strip() if c.isalnum() or c in ("-", "_")) or "manual"
    fp = sdir / f"{ts}_{safe_tag}.json"
    with fp.open("w", encoding="utf-8") as f:
        json.dump(asdict(state), f, ensure_ascii=False, indent=2)
    return fp
