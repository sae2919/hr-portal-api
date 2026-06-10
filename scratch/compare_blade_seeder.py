import re

def clean_html(text):
    text = re.sub(r'src="data:image/[^;]+;base64,[^"]+"', 'src="data:image/png;base64,[BASE64_PLACEHOLDER]"', text)
    text = re.sub(r'\s+', ' ', text)
    return text.strip()

with open(r"d:\internship\hr-panel\hr-portal-api\resources\views\pdf\offer-letter.blade.php", "r", encoding="utf-8") as f:
    blade = f.read()

# Let's extract free_intern block from blade
# It starts with @if($candidate->onboarding_type === 'free_intern') and ends before @elseif($candidate->onboarding_type === 'intern')
start_free = blade.find("@if($candidate->onboarding_type === 'free_intern')")
end_free = blade.find("@elseif($candidate->onboarding_type === 'intern')")
free_blade_html = blade[start_free:end_free]

with open(r"d:\internship\hr-panel\hr-portal-api\scratch\free_internship_offer_letter_body.html", "r", encoding="utf-8") as f:
    free_db_body = f.read()

# Clean and compare
clean_blade = clean_html(free_blade_html)
clean_db = clean_html(free_db_body)

print("Blade block length:", len(clean_blade))
print("DB block length:", len(clean_db))
# Find if DB block matches a portion of blade block
if clean_db in clean_blade:
    print("DB body is fully contained in Blade free_intern block!")
else:
    print("DB body is NOT fully contained in Blade block.")
