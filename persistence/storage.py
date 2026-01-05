from __future__ import annotations

import json
import os
from dataclasses import asdict
from pathlib import Path
from typing import Optional

from core.state import K1State

def ensure_dir(path: Path) -> None:
    path.mkdir(parents=True, exist_ok=True)

def state_file(data_dir: Path) -> Path:
    return data_dir / "state.json"

def load_state(data_dir: Path) -> K1State:
    """Load state from JSON, or return a fresh state if missing."""
    ensure_dir(data_dir)
    fp = state_file(data_dir)
    if not fp.exists():
        return K1State()
    with fp.open("r", encoding="utf-8") as f:
        raw = json.load(f)
    # Defensive parsing
    st = K1State(
        version=str(raw.get("version", "0.1-day1")),
        language=str(raw.get("language", "EN")),
        admin_enabled=bool(raw.get("admin_enabled", False)),
        memory=dict(raw.get("memory", {})) if isinstance(raw.get("memory", {}), dict) else {},
    )
    return st

def save_state(data_dir: Path, state: K1State) -> Path:
    """Persist state to JSON."""
    ensure_dir(data_dir)
    fp = state_file(data_dir)
    with fp.open("w", encoding="utf-8") as f:
        json.dump(asdict(state), f, ensure_ascii=False, indent=2)
    return fp
