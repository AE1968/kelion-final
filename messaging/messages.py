from dataclasses import dataclass
from datetime import datetime

@dataclass
class Message:
    sender: str
    recipient: str
    content: str
    sticky: bool = False
    timestamp: str = ""

    def __post_init__(self):
        if not self.timestamp:
            self.timestamp = datetime.utcnow().isoformat()
