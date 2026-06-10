with open(r"d:\internship\hr-panel\hr-portal-api\scratch\seeder_original.php", "r", encoding="utf-16") as f:
    code = f.read()

pos = code.find("'template_name' => 'full_time_offer_letter'")
if pos != -1:
    body_start = code.find("'body' => '", pos)
    body_end = code.find("',\n                'style' => '", body_start)
    body = code[body_start+11:body_end]
    
    with open("scratch/ft_orig_body.html", "w", encoding="utf-8") as out:
        out.write(body)
    print("Saved original full time body to scratch/ft_orig_body.html")
else:
    print("full_time_offer_letter not found")
