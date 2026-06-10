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

# Find section 2
sec2_pos = body.find("2. Stipend")
if sec2_pos != -1:
    print("=== FREE INTERNSHIP SECTION 2 ===")
    print(body[sec2_pos:sec2_pos+500])
else:
    print("Could not find Section 2 in free internship body!")
