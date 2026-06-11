import re

seeder_path = r"d:\internship\hr-panel\hr-portal-api\scratch\seeder_original.php"
with open(seeder_path, "r", encoding="utf-16") as f:
    code = f.read()

# find Section 10
pos = code.find("Data Security")
if pos != -1:
    print("Found around pos:", pos)
    print(repr(code[pos-100:pos+100]))
else:
    print("Not found in UTF-16!")
