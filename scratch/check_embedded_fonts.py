import fitz

doc = fitz.open("scratch/test_offer_intern_dynamic.pdf")
print("Fonts in test_offer_intern_dynamic.pdf:")
for page_num in range(len(doc)):
    page = doc[page_num]
    fonts = page.get_fonts()
    print(f"Page {page_num + 1}:")
    for f in fonts:
        print(f"  {f}")
