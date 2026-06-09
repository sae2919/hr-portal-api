from PIL import Image

def crop_logo_manual(img_path, output_path):
    img = Image.open(img_path).convert("RGBA")
    
    min_x, min_y = img.width, img.height
    max_x, max_y = 0, 0
    
    # Optimize scan by converting to loaded pixels list for speed
    pixels = img.load()
    
    for y in range(img.height):
        for x in range(img.width):
            r, g, b, a = pixels[x, y]
            # If any channel is less than 250, it is not white
            if r < 250 or g < 250 or b < 250:
                if x < min_x: min_x = x
                if x > max_x: max_x = x
                if y < min_y: min_y = y
                if y > max_y: max_y = y
                
    if max_x >= min_x and max_y >= min_y:
        # Bbox is (left, upper, right, lower)
        bbox = (min_x, min_y, max_x + 1, max_y + 1)
        cropped_img = img.crop(bbox)
        
        # Add a padding of 5px around the cropped logo
        padding = 5
        w, h = cropped_img.size
        new_w, new_h = w + 2 * padding, h + 2 * padding
        
        # Use white background
        padded_img = Image.new("RGBA", (new_w, new_h), (255, 255, 255, 255))
        padded_img.paste(cropped_img, (padding, padding), cropped_img)
        
        padded_img.save(output_path, "PNG")
        print(f"Successfully trimmed manually and saved to {output_path}. Bbox: {bbox}, size was: {w}x{h}")
    else:
        print("No logo pixels found!")

if __name__ == "__main__":
    crop_logo_manual(
        r"d:\internship\hr-panel\hr-portal-web\public\logo.jpeg",
        r"d:\internship\hr-panel\hr-portal-web\public\logo-brand.png"
    )
    crop_logo_manual(
        r"d:\internship\hr-panel\hr-portal-web\public\logo.jpeg",
        r"d:\internship\hr-panel\hr-portal-web\public\logo.png"
    )
