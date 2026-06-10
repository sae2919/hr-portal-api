import re

seeder_path = r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php"
try:
    with open(seeder_path, "r", encoding="utf-8") as f:
        code = f.read()
except UnicodeDecodeError:
    with open(seeder_path, "r", encoding="utf-16") as f:
        code = f.read()

pos = code.find("'free_internship_offer_letter'")
body_start = code.find("'body' => '", pos)
body_end = code.find("',\n                'style' => '", body_start)
body = code[body_start + 11:body_end]

print("=== FREE INTERNSHIP BODY (FIRST 2000 CHARACTERS) ===")
print(body[:2000])
