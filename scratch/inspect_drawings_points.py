import fitz

pdf_path = r"C:\Users\91756\Downloads\2026 - Internship Offer letter paid.pdf"
doc = fitz.open(pdf_path)
page = doc[0]
drawings = page.get_drawings()

for i, d in enumerate(drawings):
    if d['type'] == 'f' or d['type'] == 'fs':
        # Check if fill color is blue
        fill = d.get('fill')
        if fill:
            # check if it is not white
            if fill != (1.0, 1.0, 1.0):
                print(f"\nDrawing {i+1}:")
                print(f"  Rect: {d['rect']}")
                print(f"  Fill Color: {d['fill']}")
                print(f"  Items: {d['items']}")
