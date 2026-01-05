from typing import Dict
from .roles import Role

class ACL:
    def __init__(self):
        self.roles: Dict[str, Role] = {}

    def add_role(self, role: Role) -> None:
        self.roles[role.name] = role

    def check(self, role_name: str, permission: str) -> bool:
        role = self.roles.get(role_name)
        return role.allows(permission) if role else False
