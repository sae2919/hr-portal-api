import fitz

doc = fitz.open(r"C:\Users\91756\Downloads\Payslip Template (1).pdf")
page = doc[0]
drawings = page.get_drawings()

print(f"Total drawings: {len(drawings)}")
for idx, d in enumerate(drawings):
    if d['rect'].y1 > 170 and d['rect'].y0 < 300:
        print(f"Drawing {idx}: type={d['type']} | rect={d['rect']}")
