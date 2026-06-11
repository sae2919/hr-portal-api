with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    code = f.read()

import re

# Find the signature img tag in free_internship_offer_letter
pos = code.find("free_internship_offer_letter")
img_start = code.find("<img", pos)
img_end = code.find(">", img_start)
print("Signature image tag in offer letter:")
print(code[img_start : img_end + 1])
