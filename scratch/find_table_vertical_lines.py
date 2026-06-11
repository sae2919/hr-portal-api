import fitz

doc = fitz.open(r"C:\Users\91756\Downloads\Payslip Template.pdf")
page = doc[0]
drawings = page.get_drawings()

print("Vertical lines inside the table (y=177 to 270):")
for idx, d in enumerate(drawings):
    r = d['rect']
    width = r.x1 - r.x0
    height = r.y1 - r.y0
    # Check if it is inside the table y-range and is a vertical line
    if r.y0 >= 175 and r.y1 <= 271:
        if width < 3:
            print(f"Drawing {idx}: x0={r.x0:.2f}, x1={r.x1:.2f}, y0={r.y0:.2f}, y1={r.y1:.2f} | width={width:.2f} | height={height:.2f}")
