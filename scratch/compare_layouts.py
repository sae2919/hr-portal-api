import fitz

def print_layout(pdf_path, name):
    print(f"\n--- LAYOUT FOR: {name} ({pdf_path}) ---")
    doc = fitz.open(pdf_path)
    page = doc[0]
    
    # Sort blocks top-to-bottom, left-to-right
    blocks = page.get_text("blocks")
    blocks.sort(key=lambda b: (round(b[1], 1), round(b[0], 1)))
    
    for b in blocks:
        print(f"BBox: ({b[0]:.1f}, {b[1]:.1f}, {b[2]:.1f}, {b[3]:.1f}) | Text: {repr(b[4].strip())}")

print_layout(r"C:\Users\91756\Downloads\Payslip Template.pdf", "Full-time Reference")
print_layout(r"d:\internship\hr-panel\hr-portal-api\scratch\test_payslip_full_time.pdf", "Generated Full-time")
print_layout(r"C:\Users\91756\Downloads\Payslip Template (1).pdf", "Intern Reference")
print_layout(r"d:\internship\hr-panel\hr-portal-api\scratch\test_payslip_intern.pdf", "Generated Intern")
