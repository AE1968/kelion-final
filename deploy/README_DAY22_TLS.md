# Day 22 — TLS/HTTPS Deployment Options

This day adds an **HTTPS-ready reverse proxy** option using **Caddy**.

## Why Caddy
- Automatic TLS with Let's Encrypt (for real domains)
- Very simple config
- Great for small deployments

## Local HTTPS (self-signed, for development)
Caddy can generate an internal certificate authority.

Run:
```bash
export K1_API_TOKEN=secret
docker-compose -f docker-compose.yml -f deploy/docker-compose.caddy.yml up --build -d
```

Then access:
- https://localhost/metrics

Notes:
- Your browser will warn because it's an internal CA.
- This is intended for local dev/testing.

## Production HTTPS (real domain)
1) Point your domain DNS (A/AAAA record) to your server IP.
2) Edit `deploy/caddy/Caddyfile` and replace the production block with your domain and email.
3) Run the same compose command.

Example production block:
```caddy
example.com {
    tls you@example.com
    reverse_proxy k1:8080
}
```

## Recommendation
- For production: Caddy or a cloud load balancer terminating TLS.
- For local: `tls internal` is fast and simple.

## Security note
HSTS should be enabled **only after** you confirm HTTPS is stable in production.
