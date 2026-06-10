with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    code = f.read()

pos = code.find("'template_name' => 'free_internship_offer_letter'")
body_start = code.find("'body' => '", pos)
body_end = code.find("',\n                'style' => '", body_start)
body = code[body_start + 11:body_end]

print("=== FREE INTERNSHIP BODY ===")
print(body)
