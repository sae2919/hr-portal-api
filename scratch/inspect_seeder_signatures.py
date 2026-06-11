with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    code = f.read()

import re

templates = [
    "free_internship_offer_letter",
    "paid_internship_offer_letter",
    "full_time_offer_letter"
]

for t in templates:
    pos = code.find(f"'template_name' => '{t}'")
    if pos == -1:
        print(f"Template {t} not found.")
        continue
    body_pos = code.find("'body' => '", pos)
    if body_pos == -1:
        print(f"Body not found for {t}")
        continue
    
    end_body = code.find("',\n                'style' => '", body_pos)
    if end_body == -1:
        end_body = code.find("',\r\n                'style' => '", body_pos)
    
    body = code[body_pos + 11:end_body]
    
    # Find "Warm regards" inside the body
    wr_pos = body.find("Warm regards")
    if wr_pos != -1:
        # Find where the base64 ends and print after it
        img_end_pos = body.find("/>", wr_pos)
        if img_end_pos != -1:
            print(f"\n--- {t} signature block (after image end) ---")
            print(body[img_end_pos : img_end_pos + 600])
        else:
            print(f"\n--- {t}: image end not found ---")
    else:
        print(f"\n--- {t}: Warm regards not found ---")
