#!/usr/bin/env python3
"""
Скрипт для скачивания изображений товаров и логотипов брендов
Использует прямые URL изображений с официальных сайтов
"""

import urllib.request
import os
import ssl

# Отключаем проверку SSL для скачивания
ssl._create_default_https_context = ssl._create_unverified_context

BASE_DIR = "/Users/user/CascadeProjects/splitwise/frontend/web"
PRODUCTS_DIR = f"{BASE_DIR}/uploads/products"
BRANDS_DIR = f"{BASE_DIR}/images/brands"

def download_image(url, filename):
    """Скачивает изображение по URL"""
    try:
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        }
        req = urllib.request.Request(url, headers=headers)
        with urllib.request.urlopen(req, timeout=30) as response:
            with open(filename, 'wb') as f:
                f.write(response.read())
        print(f"✓ Скачано: {filename}")
        return True
    except Exception as e:
        print(f"✗ Ошибка при скачивании {filename}: {e}")
        return False

def main():
    # Создаем директории
    os.makedirs(BRANDS_DIR, exist_ok=True)
    
    # Логотипы брендов (альтернативные источники)
    brand_logos = {
        "nike.png": "https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Logo_NIKE.svg/512px-Logo_NIKE.svg.png",
        "adidas.png": "https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/Adidas_Logo.svg/512px-Adidas_Logo.svg.png",
        "jordan.png": "https://logodownload.org/wp-content/uploads/2021/09/jordan-brand-logo-1.png",
        "new-balance.png": "https://logodownload.org/wp-content/uploads/2017/09/new-balance-logo-1.png",
    }
    
    print("=== Скачивание логотипов брендов ===")
    for filename, url in brand_logos.items():
        filepath = os.path.join(BRANDS_DIR, filename)
        if not os.path.exists(filepath):
            download_image(url, filepath)
        else:
            print(f"• Уже существует: {filepath}")
    
    print("\n=== Готово ===")
    print(f"Логотипы сохранены в: {BRANDS_DIR}")

if __name__ == "__main__":
    main()
