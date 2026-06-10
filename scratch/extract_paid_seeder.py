with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    code = f.read()

pos = code.find("'template_name' => 'paid_internship_offer_letter'")
if pos != -1:
    # Print the lines of this template array
    print(code[pos-200:pos+1500])
else:
    print("Not found")
