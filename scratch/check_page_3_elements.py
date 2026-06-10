import fitz

doc = fitz.open("scratch/test_offer_intern_dynamic.pdf")
page = doc[2]
print("Text blocks on Page 3:")
blocks = page.get_text("blocks")
for b in blocks:
    rect = b[:4]
    txt = b[4].strip().replace("\n", " ")
    if txt:
        print(f"[{rect[0]:.1f}, {rect[1]:.1f}, {rect[2]:.1f}, {rect[3]:.1f}] '{txt}'")
