from PIL import Image, ImageDraw

def remove_background(input_path, output_path, tolerance=60):
    try:
        img = Image.open(input_path).convert("RGBA")
        width, height = img.size
        
        # Get background color from top-left pixel
        bg_color = img.getpixel((0, 0))
        
        # Create a mask image
        ImageDraw.floodfill(img, (0, 0), (0, 0, 0, 0), thresh=tolerance)
        
        # Also try other corners if they are similar
        corners = [(width-1, 0), (0, height-1), (width-1, height-1)]
        for x, y in corners:
            pixel = img.getpixel((x, y))
            # If pixel is not already transparent
            if pixel[3] != 0:
                # Check distance from original bg color
                dist = sum([abs(a - b) for a, b in zip(pixel[:3], bg_color[:3])])
                if dist < tolerance * 3: # Loose check
                     ImageDraw.floodfill(img, (x, y), (0, 0, 0, 0), thresh=tolerance)

        img.save(output_path, "PNG")
        print(f"Saved transparent image to {output_path}")
        
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    process_list = [
        ("assets/images/couple_Watch.png", "assets/images/couple_Watch_transparent.png"),
        ("assets/images/laptop.png", "assets/images/laptop_transparent_new.png")
    ]
    
    for input_path, output_path in process_list:
        try:
            remove_background(input_path, output_path, tolerance=50) # Increased tolerance slightly to 50
        except Exception as e:
            print(f"Failed to process {input_path}: {e}")
