from collections import defaultdict
from threading import Lock

class Metrics:
    def __init__(self):
        self._lock = Lock()
        self._counters = defaultdict(int)

    def inc(self, name: str, value: int = 1):
        with self._lock:
            self._counters[name] += value

    def snapshot(self):
        with self._lock:
            return dict(self._counters)
