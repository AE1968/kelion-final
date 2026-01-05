# Production Layout (Day 20)

## Run with docker-compose
```bash
export K1_API_TOKEN=secret
docker-compose up --build -d
```

## Data persistence
- Uses named volume `k1_data`
- Survives container restarts

## Stop
```bash
docker-compose down
```
