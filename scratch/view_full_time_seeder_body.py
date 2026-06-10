with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    code = f.read()

pos = code.find("'template_name' => 'full_time_offer_letter'")
if pos != -1:
    body_pos = code.find("'body' => '", pos)
    if body_pos != -1:
        end_body = code.find("',\n                'style' => '", body_pos)
        body = code[body_pos + 11:end_body]
        print(f"Full-Time Body Length: {len(body)}")
        # Let's save it to a file
        with open(r"d:\internship\hr-panel\hr-portal-api\scratch\seeder_full_time_body.html", "w", encoding="utf-8") as out:
            out.write(body)
        print("Saved seeder_full_time_body.html")
    else:
        print("body key not found")
else:
    print("full_time_offer_letter not found")
