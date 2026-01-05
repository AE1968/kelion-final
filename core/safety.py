from __future__ import annotations

import os
from dataclasses import dataclass

KILL_SWITCH_CODE = "19681"
KILL_SWITCH_ENV = "K1_KILL_SWITCH"

@dataclass(frozen=True)
class SafetyStatus:
    safe_mode: bool
    reason: str

def evaluate_safety(safe_mode_default: bool = True) -> SafetyStatus:
    """Evaluate safety status.

    Rules (Day 1):
    - If env var K1_KILL_SWITCH == 19681 => force Safe Mode
    - Else use safe_mode_default from settings
    """
    if os.getenv(KILL_SWITCH_ENV, "").strip() == KILL_SWITCH_CODE:
        return SafetyStatus(safe_mode=True, reason="Kill-switch forced Safe Mode (K1_KILL_SWITCH=19681).")
    return SafetyStatus(safe_mode=bool(safe_mode_default), reason="Default safety setting.")
