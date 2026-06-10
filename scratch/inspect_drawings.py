import fitz

pdf_path = r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf"
doc = fitz.open(pdf_path)
page = doc[0]
drawings = page.get_drawings()

print(f"Total drawings: {len(drawings)}")
for i, d in enumerate(drawings):
    print(f"\nDrawing {i+1}:")
    print(f"  Type: {d['type']}")
    print(f"  Rect: {d['rect']}")
    if 'fill' in d and d['fill']:
        print(f"  Fill Color: {d['fill']}")
    if 'color' in d and d['color']:
        print(f"  Stroke Color: {d['color']}")
    print(f"  Items count: {len(d['items'])}")
    for j, item in enumerate(d['items']):
        print(f"    Item {j+1}: {item}")
