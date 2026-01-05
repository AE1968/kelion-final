from dataclasses import dataclass
from datetime import datetime

@dataclass
class Consent:
    user_id: str
    granted: bool
    timestamp: str

    @staticmethod
    def grant(user_id: str) -> "Consent":
        return Consent(user_id=user_id, granted=True, timestamp=datetime.utcnow().isoformat())

    @staticmethod
    def revoke(user_id: str) -> "Consent":
        return Consent(user_id=user_id, granted=False, timestamp=datetime.utcnow().isoformat())
