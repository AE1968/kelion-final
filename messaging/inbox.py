from typing import List
from .messages import Message

class Inbox:
    def __init__(self):
        self.messages: List[Message] = []

    def send(self, msg: Message) -> None:
        self.messages.append(msg)

    def unread(self):
        return [m for m in self.messages if m.sticky]
