import fitz

doc = fitz.open(r"C:\Users\91756\Downloads\Payslip Template (1).pdf")
page = doc[0]
drawings = page.get_drawings()

print("Borders / Lines inside Intern Reference:")
for idx, d in enumerate(drawings):
    r = d['rect']
    width = r.x1 - r.x0
    height = r.y1 - r.y0
    if width < 3 or height < 3:
        print(f"Drawing {idx}: rect=({r.x0:.2f}, {r.y0:.2f}, {r.x1:.2f}, {r.y1:.2f}) | width={width:.2f} | height={height:.2f} | fill={d['fill']}")
