import fitz  # PyMuPDF
import os

pdf_path = r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf"
doc = fitz.open(pdf_path)

print(f"Total pages: {len(doc)}")

# Check for images on each page
for page_num in range(len(doc)):
    page = doc[page_num]
    image_list = page.get_images(full=True)
    print(f"Page {page_num+1} has {len(image_list)} images.")
    for img_idx, img in enumerate(image_list):
        xref = img[0]
        base_image = doc.extract_image(xref)
        image_bytes = base_image["image"]
        image_ext = base_image["ext"]
        out_name = f"extracted_img_p{page_num+1}_{img_idx+1}.{image_ext}"
        out_path = os.path.join(r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834", out_name)
        with open(out_path, "wb") as f:
            f.write(image_bytes)
        print(f"  Extracted image saved to: {out_path}")

# Check for vector graphics (drawings)
for page_num in range(len(doc)):
    page = doc[page_num]
    paths = page.get_drawings()
    print(f"Page {page_num+1} has {len(paths)} drawing paths.")
    if len(paths) > 0:
        print(f"  First path type: {paths[0]['type']}")
