import re

seeder_path = r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php"
try:
    with open(seeder_path, "r", encoding="utf-8") as f:
        code = f.read()
except UnicodeDecodeError:
    with open(seeder_path, "r", encoding="utf-16") as f:
        code = f.read()

pos = code.find("'paid_internship_offer_letter'")
body_start = code.find("'body' => '", pos)
body_end = code.find("',\n                'style' => '", body_start)
body = code[body_start + 11:body_end]

# Print the last 1500 chars of the body (where the signature block is)
print("Last 1500 chars of paid_internship_offer_letter body:")
print(body[-1500:])
