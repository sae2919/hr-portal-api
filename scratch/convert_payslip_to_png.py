import fitz

doc = fitz.open("scratch/test_payslip_overlaid_output.pdf")
page = doc[0]
pix = page.get_pixmap(dpi=150)
dest_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\test_overlay_payslip_coords.png"
pix.save(dest_path)
print("Saved page 1 to", dest_path)
