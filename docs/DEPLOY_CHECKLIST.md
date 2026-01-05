# Deploy Checklist

## Before deploy
- [ ] Set K1_API_TOKEN
- [ ] Choose reverse proxy (Nginx or Caddy)
- [ ] Verify docker-compose files
- [ ] Ensure volumes are persistent

## Deploy
- docker-compose up --build -d

## After deploy
- [ ] Check /metrics
- [ ] Verify API auth
- [ ] Confirm data persistence
