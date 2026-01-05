# Day 21 — Reverse Proxy Hardening (Nginx)

This day introduces an Nginx reverse proxy in front of the k1 service.

## Why
- Standard production layout: reverse proxy terminates HTTP(S), app stays on internal network
- Easy to add TLS later (Let's Encrypt / cert-manager)

## Run (HTTP)
```bash
export K1_API_TOKEN=secret
docker-compose -f docker-compose.yml -f deploy/docker-compose.nginx.yml up --build -d
```

Then access:
- http://127.0.0.1/metrics  (proxied to k1)

## HTTPS
TLS is not enabled in this repo by default.
Recommended approaches:
- Use a managed reverse proxy (Caddy/Traefik/Nginx) with Let's Encrypt
- Or put this stack behind a cloud load balancer that terminates TLS
