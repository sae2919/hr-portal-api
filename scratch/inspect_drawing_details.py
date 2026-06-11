import fitz

doc = fitz.open(r"C:\Users\91756\Downloads\Payslip Template.pdf")
page = doc[0]
drawings = page.get_drawings()

print(f"Total drawings: {len(drawings)}")
for idx, d in enumerate(drawings):
    print(f"\nDrawing {idx}: type={d['type']} | fill={d['fill']} | color={d['color']} | width={d.get('width')} | rect={d['rect']}")
    for item in d['items']:
        print(f"  Item: {item}")
