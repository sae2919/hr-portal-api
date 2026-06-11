import fitz

doc = fitz.open(r"C:\Users\91756\Downloads\Payslip Template.pdf")
page = doc[0]
drawings = page.get_drawings()

print("Employee Details Table drawings (indexes 4 to 8):")
for idx in range(4, 9):
    d = drawings[idx]
    print(f"Drawing {idx}: type={d['type']} | rect={d['rect']} | fill={d['fill']} | color={d['color']} | items={d['items']}")
