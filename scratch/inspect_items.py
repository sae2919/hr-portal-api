import fitz

doc = fitz.open(r"C:\Users\91756\Downloads\Payslip Template (1).pdf")
page = doc[0]
drawings = page.get_drawings()

for idx, d in enumerate(drawings):
    items = d['items']
    if len(items) > 0:
        print(f"Drawing {idx}: type={d['type']} | items_count={len(items)}")
        for item_idx, item in enumerate(items):
            print(f"  Item {item_idx}: {item}")
