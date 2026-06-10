seeder_path = r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php"

try:
    with open(seeder_path, "r", encoding="utf-8") as f:
        code = f.read()
    print("Read as UTF-8 successfully")
except UnicodeDecodeError:
    with open(seeder_path, "r", encoding="utf-16") as f:
        code = f.read()
    print("Read as UTF-16 successfully")

lines = code.splitlines()
print(f"Total lines: {len(lines)}")

keywords = ["free_internship_offer_letter", "paid_internship_offer_letter", "full_time_offer_letter"]
for kw in keywords:
    matches = [i+1 for i, line in enumerate(lines) if kw in line]
    print(f"Keyword '{kw}' found on lines: {matches}")
