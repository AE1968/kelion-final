from messaging.messages import Message
from messaging.inbox import Inbox

def main():
    inbox = Inbox()
    msg = Message(sender="admin", recipient="user", content="System notice", sticky=True)
    inbox.send(msg)
    print("Sent message:", msg)

if __name__ == "__main__":
    main()
