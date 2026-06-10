import re

def clean_html(text):
    # Replace base64 src attribute
    text = re.sub(r'src="data:image/[^;]+;base64,[^"]+"', 'src="data:image/png;base64,[BASE64_PLACEHOLDER]"', text)
    # Normalize whitespaces
    text = re.sub(r'\s+', ' ', text)
    return text.strip()

with open("d:\\internship\\hr-panel\\hr-portal-api\\scratch\\free_internship_offer_letter_body.html", "r", encoding="utf-8") as f:
    free_body = clean_html(f.read())

with open("d:\\internship\\hr-panel\\hr-portal-api\\scratch\\paid_internship_offer_letter_body.html", "r", encoding="utf-8") as f:
    paid_body = clean_html(f.read())

with open("d:\\internship\\hr-panel\\hr-portal-api\\scratch\\full_time_offer_letter_body.html", "r", encoding="utf-8") as f:
    full_time_body = clean_html(f.read())

# Write cleaned bodies to files for comparison
with open("d:\\internship\\hr-panel\\hr-portal-api\\scratch\\free_body_clean.html", "w", encoding="utf-8") as f:
    f.write(free_body)

with open("d:\\internship\\hr-panel\\hr-portal-api\\scratch\\paid_body_clean.html", "w", encoding="utf-8") as f:
    f.write(paid_body)

with open("d:\\internship\\hr-panel\\hr-portal-api\\scratch\\full_time_body_clean.html", "w", encoding="utf-8") as f:
    f.write(full_time_body)

print("Saved cleaned HTML bodies for comparison.")
print(f"Free body length: {len(free_body)}")
print(f"Paid body length: {len(paid_body)}")
print(f"Full-Time body length: {len(full_time_body)}")

if free_body == paid_body:
    print("Free and Paid bodies are identical!")
else:
    print("Free and Paid bodies differ.")

if paid_body == full_time_body:
    print("Paid and Full-time bodies are identical!")
else:
    print("Paid and Full-time bodies differ.")
