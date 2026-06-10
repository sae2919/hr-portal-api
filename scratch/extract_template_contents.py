import re

seeder_path = r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php"
with open(seeder_path, "r", encoding="utf-8") as f:
    code = f.read()

def extract_body_matter(template_name):
    pos = code.find(f"'{template_name}'")
    if pos == -1:
        raise ValueError(f"Template {template_name} not found!")
    
    body_start = code.find("'body' => '", pos)
    body_end = code.find("',\n                'style' => '", body_start)
    body = code[body_start + 11:body_end]
    
    # Let's clean up old header-bg, footer-bg, header table, and content-body wraps if present
    # We want to extract starting from the date block
    # The date block looks like: <div style="text-align: right; ... d-M Y or d-F Y
    # Or contains Carbon
    date_pos = body.find("Carbon")
    # Find the start of the div containing Carbon
    div_start = body.rfind("<div", 0, date_pos)
    if div_start == -1:
        div_start = body.find("<div", date_pos)
    
    # We want the content from div_start to the end of the body
    # But if there's a closing </div> at the end of the body from a content-body wrap, we should strip it
    matter = body[div_start:]
    # Check if the matter ends with a content-body closing div
    # Count opening and closing divs in matter to be safe
    # Actually, we can just strip any trailing </div> if it was added by us
    # Let's check if the matter has a </div> at the end
    # If the matter was already wrapped in content-body, it ends with </div>
    # Let's check how many times <div and </div> occur
    open_divs = matter.count("<div")
    close_divs = matter.count("</div>")
    if close_divs > open_divs:
        # Strip the last </div>
        last_div = matter.rfind("</div>")
        if last_div != -1:
            matter = matter[:last_div] + matter[last_div+6:]
            
    return matter

free_matter = extract_body_matter("free_internship_offer_letter")
paid_matter = extract_body_matter("paid_internship_offer_letter")
full_time_matter = extract_body_matter("full_time_offer_letter")

print("=== FREE INTERN MATTER (first 300 chars) ===")
print(free_matter[:300])
print("\n=== PAID INTERN MATTER (first 300 chars) ===")
print(paid_matter[:300])
print("\n=== FULL TIME MATTER (first 300 chars) ===")
print(full_time_matter[:300])
