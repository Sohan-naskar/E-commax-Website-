from PIL import Image
import os

def remove_white_bg():
    input_path = "assets/images/logo_main.png"
    output_path = "assets/images/logo_main_transparent.png"
    
    if not os.path.exists(input_path):
        print(f"Error: {input_path} not found")
        return

    try:
        img = Image.open(input_path)
        img = img.convert("RGBA")
        datas = img.getdata()

        new_data = []
        for item in datas:
            # Check for white pixels (R, G, B > 200 to catch slight variations/compression artifacts)
            if item[0] > 220 and item[1] > 220 and item[2] > 220:
                new_data.append((255, 255, 255, 0))  # Transparent
            else:
                new_data.append(item)

        img.putdata(new_data)
        img.save(output_path, "PNG")
        print(f"Successfully created transparent logo: {output_path}")
        
    except Exception as e:
        print(f"An error occurred: {e}")

if __name__ == "__main__":
    remove_white_bg()
