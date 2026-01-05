import signal
import sys

def install(on_shutdown):
    def handler(signum, frame):
        print(f"Received signal {signum}, shutting down gracefully...")
        try:
            on_shutdown()
        finally:
            sys.exit(0)

    signal.signal(signal.SIGINT, handler)
    signal.signal(signal.SIGTERM, handler)
