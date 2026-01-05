from auth.roles import Role
from auth.acl import ACL

def main():
    admin = Role(name="admin", permissions={"read", "write", "delete"})
    user = Role(name="user", permissions={"read"})

    acl = ACL()
    acl.add_role(admin)
    acl.add_role(user)

    print("admin can delete:", acl.check("admin", "delete"))
    print("user can delete:", acl.check("user", "delete"))

if __name__ == "__main__":
    main()
