import time
from runtime.signals import install

def on_shutdown():
    print("Cleanup done.")

def main():
    install(on_shutdown)
    print("Running. Press Ctrl+C to stop.")
    while True:
        time.sleep(1)

if __name__ == "__main__":
    main()
