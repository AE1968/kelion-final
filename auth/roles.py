from dataclasses import dataclass
from typing import Set

@dataclass
class Role:
    name: str
    permissions: Set[str]

    def allows(self, perm: str) -> bool:
        return perm in self.permissions
