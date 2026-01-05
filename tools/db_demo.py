from pathlib import Path
from db.sqlite import connect, init, set_kv, get_kv

def main():
    db = connect(Path(".k1/db.sqlite"))
    init(db)
    set_kv(db, "hello", "world")
    print("hello =", get_kv(db, "hello"))

if __name__ == "__main__":
    main()
