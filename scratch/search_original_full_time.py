with open(r"d:\internship\hr-panel\hr-portal-api\scratch\seeder_original.php", "r", encoding="utf-16") as f:
    code = f.read()

pos = code.find("'template_name' => 'full_time_offer_letter'")
if pos != -1:
    body_start = code.find("'body' => '", pos)
    body_end = code.find("',\n                'style' => '", body_start)
    body = code[body_start:body_end]
    
    # Print the clean text content by stripping HTML tags
    import re
    clean_text = re.sub('<[^<]+?>', ' ', body)
    # Print first 2000 chars of clean text
    print(clean_text[:2000])
else:
    print("full_time_offer_letter not found")
