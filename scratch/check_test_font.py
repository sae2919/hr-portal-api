import fitz

doc = fitz.open("scratch/test_font_goto.pdf")
page = doc[0]
print("Fonts on Page 1:")
fonts = page.get_fonts()
for f in fonts:
    print(f)
