class SimpleIndex:
    def __init__(self):
        self._data = {}

    def add(self, key: str, value: str):
        self._data[key] = value

    def search(self, query: str):
        return {k: v for k, v in self._data.items() if query.lower() in v.lower()}
