with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    lines = f.readlines()

for i, line in enumerate(lines):
    if "Carbon" in line:
        print(f"Line {i+1}: {line.strip()}")
