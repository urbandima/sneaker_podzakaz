#!/usr/bin/env python3
"""
Скрипт для скачивания фото товаров с Unsplash (бесплатные фото)
"""

import urllib.request
import os
import ssl

ssl._create_default_https_context = ssl._create_unverified_context

PRODUCTS_DIR = "/Users/user/CascadeProjects/splitwise/frontend/web/uploads/products"

# Прямые ссылки на фото с Unsplash (бесплатные для коммерческого использования)
product_images = {
    "nike-air-force-1.jpg": "https://images.unsplash.com/photo-1549298916-b41d501d3772?w=800&q=80",
    "adidas-ultraboost.jpg": "https://images.unsplash.com/photo-1587563871167-1ee9c731aefb?w=800&q=80", 
    "nike-dunk-low.jpg": "https://images.unsplash.com/photo-1556906781-9a412961c28c?w=800&q=80",
    "adidas-samba.jpg": "https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80",
    "jordan-4-retro.jpg": "https://images.unsplash.com/photo-1516478177764-9fe5bd7e9717?w=800&q=80",
}

def download_image(url, filename):
    try:
        headers = {
            'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
        }
        req = urllib.request.Request(url, headers=headers)
        with urllib.request.urlopen(req, timeout=30) as response:
            with open(filename, 'wb') as f:
                f.write(response.read())
        print(f"✓ {filename}")
        return True
    except Exception as e:
        print(f"✗ {filename}: {e}")
        return False

if __name__ == "__main__":
    os.makedirs(PRODUCTS_DIR, exist_ok=True)
    print("=== Скачивание фото товаров ===")
    
    for filename, url in product_images.items():
        filepath = os.path.join(PRODUCTS_DIR, filename)
        download_image(url, filepath)
    
    print("\n=== Готово ===")
