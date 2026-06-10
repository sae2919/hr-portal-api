import fitz
import sys

sys.stdout = open(r"d:\internship\hr-panel\hr-portal-api\scratch\gen_layout_details.txt", "w", encoding="utf-8")

pdf_path = r"d:\internship\hr-panel\hr-portal-api\scratch\test_offer_intern_dynamic.pdf"
doc = fitz.open(pdf_path)

for page_num in range(len(doc)):
    page = doc[page_num]
    print(f"\n================ PAGE {page_num + 1} ==================")
    blocks = page.get_text("dict")["blocks"]
    for block in blocks:
        if "lines" in block:
            for line in block["lines"]:
                for span in line["spans"]:
                    text = span["text"].strip()
                    if text:
                        bbox = span["bbox"]
                        font = span["font"]
                        size = span["size"]
                        color = span["color"]
                        print(f"[{bbox[0]:.1f}, {bbox[1]:.1f}, {bbox[2]:.1f}, {bbox[3]:.1f}] Font='{font}' Size={size:.1f} Color={color} | '{text}'")

sys.stdout.close()
print("Done! Saved details to gen_layout_details.txt")
