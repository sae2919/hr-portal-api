import fitz

doc = fitz.open(r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf")
page = doc[2] # Page 3 (0-indexed)
blocks = page.get_text("dict")["blocks"]

for block in blocks:
    if "lines" in block:
        for line in block["lines"]:
            for span in line["spans"]:
                text = span["text"].strip()
                if text:
                    print(f"Text: {text[:40]:<40} Font: {span['font']:<15} Size: {span['size']:.1f}")
