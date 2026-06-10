with open(r"d:\internship\hr-panel\hr-portal-api\scratch\seeder_original.php", "r", encoding="utf-16") as f:
    code = f.read()

pos = code.find("'template_name' => 'full_time_offer_letter'")
if pos != -1:
    body_start = code.find("'body' => '", pos)
    body_end = code.find("',\n                'style' => '", body_start)
    print(code[body_start:body_start+1500])
    print("...")
    print(code[body_end-1500:body_end])
else:
    print("full_time_offer_letter not found")
