from http.server import BaseHTTPRequestHandler
import json
from urllib.parse import urlparse, parse_qs
from pathlib import Path
from db.sqlite import connect, init, set_kv, get_kv

class KVHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        parsed = urlparse(self.path)
        if parsed.path != "/kv":
            self.send_response(404)
            self.end_headers()
            return

        qs = parse_qs(parsed.query)
        key = qs.get("key", [None])[0]
        if not key:
            self.send_response(400)
            self.end_headers()
            return

        db = connect(Path(".k1/db.sqlite"))
        init(db)
        val = get_kv(db, key)

        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.end_headers()
        self.wfile.write(json.dumps({"key": key, "value": val}).encode())

    def do_POST(self):
        if self.path != "/kv":
            self.send_response(404)
            self.end_headers()
            return

        length = int(self.headers.get("Content-Length", "0"))
        data = json.loads(self.rfile.read(length).decode())
        key = data.get("key")
        value = data.get("value")

        if key is None or value is None:
            self.send_response(400)
            self.end_headers()
            return

        db = connect(Path(".k1/db.sqlite"))
        init(db)
        set_kv(db, key, value)

        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.end_headers()
        self.wfile.write(json.dumps({"ok": True}).encode())
