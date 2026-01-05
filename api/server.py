from http.server import HTTPServer
from api.kv_api import KVHandler
from api.auth import check
from api.rate_limit import RateLimiter
from metrics.metrics import Metrics
from runtime.signals import install
import json

limiter = RateLimiter(max_requests=5, window_seconds=60)
metrics = Metrics()

class AuthRateMetricsHandler(KVHandler):
    def _auth(self):
        if not check(self.headers):
            metrics.inc("auth_fail")
            self.send_response(401)
            self.end_headers()
            return False
        return True

    def _rate(self):
        client = self.client_address[0]
        if not limiter.allow(client):
            metrics.inc("rate_limited")
            self.send_response(429)
            self.end_headers()
            return False
        return True

    def do_GET(self):
        if self.path == "/metrics":
            self.send_response(200)
            self.send_header("Content-Type", "application/json")
            self.end_headers()
            self.wfile.write(json.dumps(metrics.snapshot()).encode())
            return

        if not self._auth() or not self._rate():
            return
        metrics.inc("get_requests")
        super().do_GET()

    def do_POST(self):
        if not self._auth() or not self._rate():
            return
        metrics.inc("post_requests")
        super().do_POST()

def run(host="0.0.0.0", port=8080):
    """
    Start the K1 API server
    
    Args:
        host: Host to bind to (default: 0.0.0.0 for Railway compatibility)
        port: Port to listen on (default: 8080)
    """
    httpd = HTTPServer((host, port), AuthRateMetricsHandler)
    install(lambda: httpd.server_close())
    print(f"API running on http://{host}:{port}")
    httpd.serve_forever()

if __name__ == "__main__":
    run()
