with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    code = f.read()

# Let's search for the signature img tag
pos = code.find("<div class=\"signature-section\">")
if pos != -1:
    img_pos = code.find("<img", pos)
    if img_pos != -1:
        img_end = code.find(">", img_pos)
        print(code[img_pos:img_end+1])
else:
    print("signature-section not found")
