import fitz

doc = fitz.open("scratch/test_exit_dynamic.pdf")
page = doc[0]
with open("scratch/exit_text_out.txt", "w", encoding="utf-8") as f:
    f.write(page.get_text())
print("Saved text to scratch/exit_text_out.txt")
