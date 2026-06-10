import re

seeder_path = r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php"
try:
    with open(seeder_path, "r", encoding="utf-8") as f:
        code = f.read()
except UnicodeDecodeError:
    with open(seeder_path, "r", encoding="utf-16") as f:
        code = f.read()

# Let's find each template and print where the page breaks are located
for name in ["free_internship_offer_letter", "paid_internship_offer_letter", "full_time_offer_letter"]:
    pos = code.find(f"'{name}'")
    if pos != -1:
        body_start = code.find("'body' => '", pos)
        body_end = code.find("',\n                'style' => '", body_start)
        body = code[body_start + 11:body_end]
        
        print(f"\nTemplate: {name}")
        page_breaks = [m.start() for m in re.finditer(r'page-break', body)]
        print(f"Page breaks count: {len(page_breaks)}")
        # Print some surrounding text for each page break
        for idx, pb_pos in enumerate(page_breaks):
            context = body[max(0, pb_pos - 100): min(len(body), pb_pos + 150)]
            print(f"  Break #{idx+1}: ... {context} ...")
