seeder_path = r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php"

with open(seeder_path, "r", encoding="utf-8") as f:
    code = f.read()

pos = code.find("monthly_payslip_template")
print("pos:", pos)
if pos != -1:
    line_no = code.count("\n", 0, pos) + 1
    print("Found at line:", line_no)
    print(code[pos-200:pos+1000])
else:
    print("Not found in seeder!")
