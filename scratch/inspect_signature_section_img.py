with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    code = f.read()

pos = code.find("class=\"signature-section\"")
if pos != -1:
    img_start = code.find("<img", pos)
    img_end = code.find(">", img_start)
    print("Signature image tag in offer letter:")
    print(code[img_start : img_end + 1])
else:
    print("signature-section class not found in seeder")
