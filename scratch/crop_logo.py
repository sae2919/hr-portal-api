from PIL import Image, ImageChops

def trim_white_borders(img_path, output_path):
    img = Image.open(img_path).convert("RGBA")
    
    # Create a white background image of the same size
    bg = Image.new("RGBA", img.size, (255, 255, 255, 255))
    diff = ImageChops.difference(img, bg)
    
    # getbbox() finds the bounding box of non-zero regions in the difference image
    bbox = diff.getbbox()
    if bbox:
        cropped_img = img.crop(bbox)
        
        # Add a small padding of 5px around the cropped logo
        padding = 5
        w, h = cropped_img.size
        new_w, new_h = w + 2 * padding, h + 2 * padding
        
        # We use a white background for the padded image
        padded_img = Image.new("RGBA", (new_w, new_h), (255, 255, 255, 255))
        # Paste cropped logo onto it
        padded_img.paste(cropped_img, (padding, padding), cropped_img)
        
        padded_img.save(output_path, "PNG")
        print(f"Successfully trimmed and saved to {output_path}. Bbox: {bbox}")
    else:
        print("No bounding box found (image might be entirely white)")

if __name__ == "__main__":
    trim_white_borders(
        r"d:\internship\hr-panel\hr-portal-web\public\logo.jpeg",
        r"d:\internship\hr-panel\hr-portal-web\public\logo-brand.png"
    )
    # Also overwrite logo.png for consistency
    trim_white_borders(
        r"d:\internship\hr-panel\hr-portal-web\public\logo.jpeg",
        r"d:\internship\hr-panel\hr-portal-web\public\logo.png"
    )
