import fitz
with open("scratch/dumped_ref_text.txt", "w", encoding="utf-8") as f:
    doc = fitz.open(r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf")
    for i, page in enumerate(doc):
        f.write(f"\n=== Page {i+1} ===\n")
        f.write(page.get_text())
print("Saved reference PDF text to scratch/dumped_ref_text.txt")
