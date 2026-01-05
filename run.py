from __future__ import annotations

import sys
from pathlib import Path
from typing import Any

from core.identity import Identity
from core.safety import evaluate_safety
from persistence.storage import load_state, save_state
from persistence.snapshots import create_snapshot

def parse_simple_yaml(text: str) -> dict[str, str]:
    """Parse simple 'key: value' YAML without dependencies (Day 1).

    Supports:
    - comments starting with '#'
    - blank lines
    - scalar values only

    Returns strings; callers convert types as needed.
    """
    out: dict[str, str] = {}
    for line in text.splitlines():
        line = line.strip()
        if not line or line.startswith("#"):
            continue
        if ":" not in line:
            continue
        k, v = line.split(":", 1)
        out[k.strip()] = v.strip()
    return out

def load_settings(project_root: Path) -> dict[str, Any]:
    cfg = project_root / "config" / "settings.yaml"
    raw = parse_simple_yaml(cfg.read_text(encoding="utf-8"))
    # Basic type coercions
    def to_bool(s: str, default: bool) -> bool:
        s2 = s.strip().lower()
        if s2 in ("true", "yes", "1", "on"):
            return True
        if s2 in ("false", "no", "0", "off"):
            return False
        return default

    settings: dict[str, Any] = {
        "app_name": raw.get("app_name", "k1"),
        "default_language": raw.get("default_language", "EN"),
        "data_dir": raw.get("data_dir", ".k1"),
        "safe_mode_default": to_bool(raw.get("safe_mode_default", "true"), True),
        "admin_enabled_default": to_bool(raw.get("admin_enabled_default", "false"), False),
    }
    return settings

def print_help() -> None:
    print(
        """k1 Day 1 — commands

Usage:
  python run.py                 # default: status
  python run.py status
  python run.py show
  python run.py set <key> <value>
  python run.py save
  python run.py snapshot [tag]

Examples:
  python run.py set user.name Adrian
  python run.py snapshot day1
"""
    )

def coerce_value(val: str) -> Any:
    v = val.strip()
    low = v.lower()
    if low in ("true", "false"):
        return low == "true"
    # ints
    if v.isdigit() or (v.startswith("-") and v[1:].isdigit()):
        try:
            return int(v)
        except ValueError:
            pass
    return v

def main(argv: list[str]) -> int:
    project_root = Path(__file__).resolve().parent
    settings = load_settings(project_root)

    ident = Identity()
    data_dir = project_root / str(settings["data_dir"])
    state = load_state(data_dir)
    # Apply defaults only if state is fresh-ish
    if not state.language:
        state.language = str(settings["default_language"])
    # Safety
    safety = evaluate_safety(bool(settings["safe_mode_default"]))

    cmd = argv[1] if len(argv) > 1 else "status"

    if cmd in ("-h", "--help", "help"):
        print_help()
        return 0

    if cmd == "status":
        print(ident.summary())
        print(f"Safe Mode: {safety.safe_mode} ({safety.reason})")
        print(f"Persistence: ACTIVE (local JSON)")
        print(f"Data dir: {data_dir}")
        return 0

    if cmd == "show":
        print("State:")
        print(f"  version: {state.version}")
        print(f"  language: {state.language}")
        print(f"  admin_enabled: {state.admin_enabled}")
        print(f"  memory_keys: {list(state.memory.keys())}")
        return 0

    if cmd == "set":
        if len(argv) < 4:
            print("ERROR: set requires <key> <value>")
            return 2
        key = argv[2]
        value = coerce_value(" ".join(argv[3:]))
        # Special top-level keys
        if key == "language":
            state.language = str(value)
        elif key == "admin.enabled":
            state.admin_enabled = bool(value)
        else:
            state.set_path(key, value)
        print(f"OK: set {key} = {value!r}")
        return 0

    if cmd == "save":
        fp = save_state(data_dir, state)
        print(f"Saved: {fp}")
        return 0

    if cmd == "snapshot":
        tag = argv[2] if len(argv) > 2 else "manual"
        fp = create_snapshot(data_dir, state, tag=tag)
        print(f"Snapshot: {fp}")
        return 0

    print(f"Unknown command: {cmd}")
    print_help()
    return 2

if __name__ == "__main__":
    raise SystemExit(main(sys.argv))
