#!/bin/bash
# Convert images to WebP format for better performance
# Run this script from the project root directory

echo "Converting images to WebP format..."

# Check if cwebp is available
if ! command -v cwebp &> /dev/null; then
    echo "cwebp not found. Install with: brew install webp (Mac) or apt-get install webp (Linux)"
    exit 1
fi

# Convert images in assets/images
find assets/images -type f \( -name "*.jpg" -o -name "*.jpeg" -o -name "*.png" \) | while read file; do
    webp_file="${file%.*}.webp"
    if [ ! -f "$webp_file" ]; then
        echo "Converting: $file -> $webp_file"
        cwebp -q 80 "$file" -o "$webp_file" 2>/dev/null
    else
        echo "Skipping (exists): $webp_file"
    fi
done

# Convert images in uploads (if exists and contains images)
if [ -d "uploads" ]; then
    find uploads -type f \( -name "*.jpg" -o -name "*.jpeg" -o -name "*.png" \) | while read file; do
        webp_file="${file%.*}.webp"
        if [ ! -f "$webp_file" ]; then
            echo "Converting: $file -> $webp_file"
            cwebp -q 80 "$file" -o "$webp_file" 2>/dev/null
        fi
    done
fi

echo "Done! WebP conversion complete."
echo ""
echo "To use WebP images, update your templates to use the ImageHelper class:"
echo "  <?= ImageHelper::webp('/path/to/image.jpg', 'Alt text', 300, 375) ?>"
