import sqlite3
from pathlib import Path

def connect(db_path: Path):
    db_path.parent.mkdir(parents=True, exist_ok=True)
    return sqlite3.connect(db_path)

def init(db):
    cur = db.cursor()
    cur.execute(
        "CREATE TABLE IF NOT EXISTS kv (key TEXT PRIMARY KEY, value TEXT)"
    )
    db.commit()

def set_kv(db, key: str, value: str):
    cur = db.cursor()
    cur.execute(
        "INSERT INTO kv(key, value) VALUES(?, ?) "
        "ON CONFLICT(key) DO UPDATE SET value=excluded.value",
        (key, value),
    )
    db.commit()

def get_kv(db, key: str):
    cur = db.cursor()
    cur.execute("SELECT value FROM kv WHERE key=?", (key,))
    row = cur.fetchone()
    return row[0] if row else None
