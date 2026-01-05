# k1 — Day 1 Foundation (Locked Baseline Compatible)

This Day 1 deliverable is a **minimal, runnable foundation** for k1 that:
- keeps a stable project structure for incremental daily delivery
- implements identity, safety (including kill-switch concept), state, local persistence, and snapshots
- avoids external dependencies (stdlib only)

## Quick start

```bash
cd k1_day1
python run.py
```

## CLI options

```bash
python run.py status
python run.py set admin.enabled true
python run.py save
python run.py snapshot "day1"
python run.py show
```

## Kill-switch / Safe Mode

If `K1_KILL_SWITCH` equals `19681`, Safe Mode is forced.

Example:

```bash
K1_KILL_SWITCH=19681 python run.py status
```

## Persistence paths

By default, local data is stored under:

- `.k1/state.json`
- `.k1/snapshots/<timestamp>_<tag>.json`

You can change the base directory in `config/settings.yaml`.

## Notes

- This is **Day 1 only**: no DB, no encryption, no admin UI.
- The project is designed to be extended in Day 2+ without breaking Day 1 behavior.
