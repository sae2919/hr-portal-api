import re
import os

seeder_path = r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php"
try:
    with open(seeder_path, "r", encoding="utf-8") as f:
        code = f.read()
except UnicodeDecodeError:
    with open(seeder_path, "r", encoding="utf-16") as f:
        code = f.read()

def extract_body_matter(template_name):
    pos = code.find(f"'{template_name}'")
    if pos == -1:
        raise ValueError(f"Template {template_name} not found!")
    
    body_start = code.find("'body' => '", pos)
    body_end = code.find("',\n                'style' => '", body_start)
    body = code[body_start + 11:body_end]
    
    date_pos = body.find("Carbon")
    div_start = body.rfind("<div", 0, date_pos)
    if div_start == -1:
        div_start = body.find("<div", date_pos)
        
    matter = body[div_start:]
    
    open_divs = matter.count("<div")
    close_divs = matter.count("</div>")
    if close_divs > open_divs:
        last_div = matter.rfind("</div>")
        if last_div != -1:
            matter = matter[:last_div] + matter[last_div+6:]
            
    return matter.strip()

for name in ["free_internship_offer_letter", "paid_internship_offer_letter", "full_time_offer_letter"]:
    matter = extract_body_matter(name)
    print(f"\n==========================================")
    print(f"Template: {name} (Length: {len(matter)})")
    print(f"First 400 characters:")
    print(matter[:400])
    print(f"Last 400 characters:")
    print(matter[-400:])
