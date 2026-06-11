import fitz

doc = fitz.open("scratch/test_overlay_output.pdf")
print("Number of pages:", len(doc))

page = doc[0]
print("--- PAGE 1 TEXT ---")
print(page.get_text())
