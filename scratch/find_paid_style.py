with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    code = f.read()

pos_paid = code.find("'template_name' => 'paid_internship_offer_letter'")
if pos_paid != -1:
    style_start = code.find("'style' => '", pos_paid)
    if style_start != -1:
        # Find the end of the style array value. In PHP, style ends with ',
        # followed by the next array element: 'active_status' => 1,
        style_end = code.find("',\n                'active_status' => 1,", style_start)
        if style_end == -1:
            style_end = code.find("',\r\n                'active_status' => 1,", style_start)
        
        style = code[style_start + 12:style_end]
        print(f"Paid style length: {len(style)}")
        print(style[:200] + "...")
        print("...")
        print(style[-200:])
    else:
        print("style start not found")
else:
    print("paid template not found")
