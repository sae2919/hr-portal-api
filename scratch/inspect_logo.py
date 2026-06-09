from PIL import Image

img = Image.open(r"d:\internship\hr-panel\hr-portal-web\public\logo.jpeg")
print("size:", img.size)
print("mode:", img.mode)

# Let's count how many pixels are not white
non_white = 0
for y in range(img.height):
    for x in range(img.width):
        p = img.getpixel((x, y))
        # if p is tuple, check it
        if isinstance(p, tuple):
            if any(c < 250 for c in p[:3]):
                non_white += 1
        else:
            if p < 250:
                non_white += 1

print("non-white pixels:", non_white)
