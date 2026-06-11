with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    code = f.read()

import re

# Find the signature image which is jpeg base64
pos = code.find("data:image/jpeg;base64")
if pos != -1:
    img_start = code.rfind("<img", 0, pos)
    img_end = code.find(">", pos)
    print("Found signature image tag:")
    print(code[img_start : img_end + 1])
else:
    print("Signature image data not found")
