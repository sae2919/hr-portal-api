import fitz

doc = fitz.open("scratch/test_offer_intern_dynamic.pdf")
page = doc[0]
print("Images on Page 1:")
for info in page.get_images(full=True):
    xref = info[0]
    rects = page.get_image_rects(xref)
    print(f"Image xref={xref}: placed rects={rects}")
