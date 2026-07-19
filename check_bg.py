from PIL import Image
import os

try:
    img = Image.open("assets/images/headphone_image.jpg")
    rgb_im = img.convert('RGB')
    width, height = img.size
    
    # Check corners
    corners = [
        (0, 0),
        (width-1, 0),
        (0, height-1),
        (width-1, height-1)
    ]
    
    print(f"Image Size: {width}x{height}")
    for x, y in corners:
        r, g, b = rgb_im.getpixel((x, y))
        print(f"Pixel at ({x}, {y}): R={r}, G={g}, B={b}")
        
except Exception as e:
    print(f"Error: {e}")
