import re

with open(r"d:\internship\hr-panel\hr-portal-api\database\seeders\MailTemplateSeeder.php", "r", encoding="utf-8") as f:
    code = f.read()

# Let's find template arrays in php
# An array starts with [ and ends with ]
# We can find blocks matching 'template_name' => '...'
# Let's find each template name and print its keys and first few/last few characters of body/style.

templates = ['free_internship_offer_letter', 'paid_internship_offer_letter', 'full_time_offer_letter']
for t in templates:
    pos = code.find(f"'{t}'")
    if pos == -1:
        pos = code.find(f'"{t}"')
    if pos != -1:
        # Search backwards to find the start of the array [
        start_idx = code.rfind('[', 0, pos)
        # Search forwards to find the matching closing bracket
        # Let's count brackets
        bracket_count = 1
        end_idx = start_idx + 1
        while bracket_count > 0 and end_idx < len(code):
            if code[end_idx] == '[':
                bracket_count += 1
            elif code[end_idx] == ']':
                bracket_count -= 1
            end_idx += 1
        
        block = code[start_idx:end_idx]
        print(f"\n==================== DETAILS FOR {t} ====================")
        # Find style key
        style_match = re.search(r"'style'\s*=>\s*'(.*?)',", block, re.DOTALL)
        if not style_match:
            style_match = re.search(r'"style"\s*=>\s*"(.*?)",', block, re.DOTALL)
        if style_match:
            style_val = style_match.group(1)
            print(f"Style length: {len(style_val)}")
            print(f"Style start: {style_val[:200]}...")
            print(f"Style end: ...{style_val[-200:]}")
        else:
            print("Style not found in seeder block!")

        # Find body key
        body_match = re.search(r"'body'\s*=>\s*'(.*?)',", block, re.DOTALL)
        if not body_match:
            body_match = re.search(r'"body"\s*=>\s*"(.*?)",', block, re.DOTALL)
        if body_match:
            body_val = body_match.group(1)
            print(f"Body length: {len(body_val)}")
            print(f"Body start: {body_val[:200]}...")
            print(f"Body end: ...{body_val[-200:]}")
        else:
            print("Body not found in seeder block!")
    else:
        print(f"{t} not found in seeder")
