with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    code = f.read()

pos = code.find("'template_name' => 'full_time_offer_letter'")
if pos != -1:
    end_template = code.find("],", pos)
    # Let's print the lines of this template array
    print(code[pos-200:pos+1500])
else:
    print("Not found")
