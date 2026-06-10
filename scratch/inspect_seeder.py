with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    code = f.read()

templates = ['free_internship_offer_letter', 'paid_internship_offer_letter', 'full_time_offer_letter']
for t in templates:
    pos = code.find(t)
    if pos != -1:
        line_num = code[:pos].count('\n') + 1
        print(f"Found '{t}' at line {line_num}")
    else:
        print(f"'{t}' not found in seeder.")
