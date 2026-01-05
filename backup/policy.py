from dataclasses import dataclass

@dataclass(frozen=True)
class BackupPolicy:
    daily: bool = True
    monthly: bool = True
    immutable: bool = True

    def summary(self) -> str:
        return (
            f"BackupPolicy(daily={self.daily}, "
            f"monthly={self.monthly}, immutable={self.immutable})"
        )
