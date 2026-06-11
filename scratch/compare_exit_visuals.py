import os
from PIL import Image, ImageChops, ImageDraw

ref_img_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\reference_exit_page_1.png"
gen_img_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\test_exit_page_1.png"
diff_img_path = r"C:\Users\91756\.gemini\antigravity-ide\brain\7d3c3efd-0e98-4091-b648-313ef626c834\exit_visual_diff.png"

if not os.path.exists(ref_img_path):
    print(f"Error: {ref_img_path} does not exist.")
    exit(1)
if not os.path.exists(gen_img_path):
    print(f"Error: {gen_img_path} does not exist.")
    exit(1)

ref_img = Image.open(ref_img_path).convert("RGB")
gen_img = Image.open(gen_img_path).convert("RGB")

print(f"Reference Image Size: {ref_img.size}")
print(f"Generated Image Size: {gen_img.size}")

if ref_img.size != gen_img.size:
    print("Warning: Image sizes differ! Resizing generated to match reference size...")
    gen_img = gen_img.resize(ref_img.size, Image.Resampling.LANCZOS)

# Calculate difference
diff = ImageChops.difference(ref_img, gen_img)
diff_pixels = diff.getdata()

# Count number of non-matching pixels
threshold = 15  # Allowance for anti-aliasing/rendering engine differences
diff_count = sum(1 for p in diff_pixels if max(p) > threshold)
total_pixels = ref_img.size[0] * ref_img.size[1]
pct_diff = (diff_count / total_pixels) * 100

print(f"Total Pixels: {total_pixels}")
print(f"Different Pixels (threshold={threshold}): {diff_count} ({pct_diff:.2f}%)")

# Save diff image highlighting differences
# Let's create an overlay where differences are highlighted in red on top of a grayed out version of the reference
bg = ref_img.copy()
bg = bg.convert("L").convert("RGB") # gray
draw = ImageDraw.Draw(bg)

# Create a visual side-by-side or a blend
# To make it very easy to see, let's create a side-by-side image: [Reference | Generated | Diff]
width, height = ref_img.size
side_by_side = Image.new("RGB", (width * 2, height))
side_by_side.paste(ref_img, (0, 0))
side_by_side.paste(gen_img, (width, 0))

# Also draw red rectangles or highlights on side_by_side where diff is large
# To avoid being slow, we scan in blocks
block_size = 8
draw_gen = ImageDraw.Draw(gen_img)
for y in range(0, height, block_size):
    for x in range(0, width, block_size):
        # check if this block has any significant diff
        has_diff = False
        for by in range(y, min(y + block_size, height)):
            for bx in range(x, min(x + block_size, width)):
                p = diff.getpixel((bx, by))
                if max(p) > threshold:
                    has_diff = True
                    break
            if has_diff:
                break
        if has_diff:
            # Highlight this block with a semi-transparent red box or border
            draw_gen.rectangle([x, y, x + block_size, y + block_size], outline="red", width=1)

highlighted_sbs = Image.new("RGB", (width * 2, height))
highlighted_sbs.paste(ref_img, (0, 0))
highlighted_sbs.paste(gen_img, (width, 0))
highlighted_sbs.save(diff_img_path)
print(f"Saved side-by-side highlighted comparison image to {diff_img_path}")
