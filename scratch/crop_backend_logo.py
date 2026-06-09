from PIL import Image

def crop_logo_manual(img_path, output_path):
    img = Image.open(img_path).convert("RGBA")
    
    min_x, min_y = img.width, img.height
    max_x, max_y = 0, 0
    
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
        bbox = (min_x, min_y, max_x + 1, max_y + 1)
        cropped_img = img.crop(bbox)
        
        padding = 5
        w, h = cropped_img.size
        new_w, new_h = w + 2 * padding, h + 2 * padding
        
        padded_img = Image.new("RGBA", (new_w, new_h), (255, 255, 255, 255))
        padded_img.paste(cropped_img, (padding, padding), cropped_img)
        
        padded_img.save(output_path, "PNG")
        print(f"Successfully trimmed backend logo manually and saved to {output_path}. Bbox: {bbox}, size was: {w}x{h}")
    else:
        print("No logo pixels found!")

if __name__ == "__main__":
    crop_logo_manual(
        r"D:\internship\hr-panel\hr-portal-api\public\storage/branding/HjtLcsg3OwrX52OsxffkN3Y9izHJRKQ4vwUxFvHo.png",
        r"D:\internship\hr-panel\hr-portal-api\public\storage/branding/HjtLcsg3OwrX52OsxffkN3Y9izHJRKQ4vwUxFvHo.png"
    )
