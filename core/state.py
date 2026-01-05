from __future__ import annotations

from dataclasses import dataclass, field
from typing import Any, Dict

@dataclass
class K1State:
    """Runtime state (persistable)."""
    version: str = "0.1-day1"
    language: str = "EN"
    admin_enabled: bool = False
    memory: Dict[str, Any] = field(default_factory=dict)

    def set_path(self, dotted_key: str, value: Any) -> None:
        """Set nested dict path in `memory` using dot notation.

        Example:
            state.set_path("user.name", "Adrian")
        """
        parts = [p for p in dotted_key.split(".") if p]
        if not parts:
            raise ValueError("Key is empty.")
        cur = self.memory
        for p in parts[:-1]:
            nxt = cur.get(p)
            if not isinstance(nxt, dict):
                nxt = {}
                cur[p] = nxt
            cur = nxt
        cur[parts[-1]] = value

    def get_path(self, dotted_key: str) -> Any:
        parts = [p for p in dotted_key.split(".") if p]
        if not parts:
            return None
        cur: Any = self.memory
        for p in parts:
            if not isinstance(cur, dict) or p not in cur:
                return None
            cur = cur[p]
        return cur
