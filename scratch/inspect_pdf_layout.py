import fitz  # PyMuPDF
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

pdf_path = r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf"
doc = fitz.open(pdf_path)

print(f"PDF Page Count: {len(doc)}")

for page_num in range(len(doc)):
    page = doc[page_num]
    rect = page.rect
    print(f"\n--- Page {page_num + 1} (Width: {rect.width}, Height: {rect.height}) ---")
    
    # 1. Images and their rects
    image_info = page.get_images(full=True)
    print(f"Images count: {len(image_info)}")
    for info in image_info:
        xref = info[0]
        rects = page.get_image_rects(xref)
        base_image = doc.extract_image(xref)
        print(f"  Image xref={xref}: size={base_image['size']}, ext={base_image['ext']}, placed rects={rects}")

    # 2. Text blocks
    print("Text blocks:")
    text_page = page.get_text("blocks")
    for block in text_page:
        rect = block[:4]
        text = block[4].strip().replace("\n", " ")
        if len(text) > 0:
            print(f"  Text at {rect}: '{text[:100]}'")

    # 3. Drawings (Vector lines/shapes)
    drawings = page.get_drawings()
    print(f"Drawings count: {len(drawings)}")
    for idx, dwg in enumerate(drawings[:20]):
        print(f"  Drawing #{idx+1}: type={dwg['type']}, rect={dwg['rect']}, fill={dwg.get('fill')}, color={dwg.get('color')}, width={dwg.get('width')}")
