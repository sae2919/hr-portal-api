import fitz

doc = fitz.open("scratch/paid_internship_offer_letter.pdf")
page = doc[0]
with open("scratch/paid_text_out.txt", "w", encoding="utf-8") as f:
    f.write(page.get_text())
print("Saved text to scratch/paid_text_out.txt")
