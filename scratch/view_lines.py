import sys
sys.stdout.reconfigure(encoding='utf-8')

with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    lines = f.readlines()

for idx in range(420, 480):
    if idx < len(lines):
        print(f"{idx+1}: {lines[idx]}", end="")
