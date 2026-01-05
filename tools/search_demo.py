from search.index import SimpleIndex

def main():
    idx = SimpleIndex()
    idx.add("1", "System initialized")
    idx.add("2", "Admin enabled")
    print(idx.search("admin"))

if __name__ == "__main__":
    main()
