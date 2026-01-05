# Recovery Procedure

## Scenario: container crash
1. docker-compose down
2. docker-compose up -d
(data preserved in volumes)

## Scenario: host failure
1. Restore volume backup
2. docker-compose up -d

## Scenario: full rebuild
1. Pull latest ZIP (matching version)
2. Restore .k1 volume
3. Start stack
