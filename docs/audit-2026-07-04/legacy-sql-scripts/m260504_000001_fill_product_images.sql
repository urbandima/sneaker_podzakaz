-- CMP-146: Fill product images for all active products
-- Products 1-8: already have main_image_url (Unsplash) + product_image records; fix is_main=1
-- Products 9-10: have broken local paths; replace with Unsplash URLs + add product_image
-- Products 11-40: completely missing images; add main_image_url + product_image records

SET @ts = UNIX_TIMESTAMP();

-- Fix is_main for products 1-8: set is_main=1 on the first (sort_order=1) record
UPDATE product_image pi
JOIN (
    SELECT MIN(id) as min_id, product_id
    FROM product_image
    WHERE product_id BETWEEN 1 AND 8
    GROUP BY product_id
) first ON first.min_id = pi.id
SET pi.is_main = 1;

-- Products 9-10: update main_image + main_image_url to Unsplash URLs
UPDATE product SET
    main_image     = 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80',
    main_image_url = 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80'
WHERE id = 9;  -- Nike Air Jordan 1

UPDATE product SET
    main_image     = 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80',
    main_image_url = 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80'
WHERE id = 10;  -- Adidas Stan Smith

-- Products 11-40: set main_image_url
-- Nike
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80' WHERE id = 11; -- AF1
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=800&q=80' WHERE id = 12; -- AM270
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80' WHERE id = 13; -- React Infinity
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80' WHERE id = 14; -- Pegasus 39
-- Adidas
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80' WHERE id = 15; -- NMD R1
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80' WHERE id = 16; -- Yeezy 350
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80' WHERE id = 17; -- Forum Low
-- New Balance
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?w=800&q=80' WHERE id = 18; -- 990v5
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80' WHERE id = 19; -- 2002R
-- Puma
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1556906781-9a412961c28c?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1556906781-9a412961c28c?w=800&q=80' WHERE id = 20; -- Suede
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1543508282-6319a3e2621f?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1543508282-6319a3e2621f?w=800&q=80' WHERE id = 21; -- RS-X Puzzle
-- Converse
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80' WHERE id = 22; -- All Star Hi
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800&q=80' WHERE id = 23; -- Run Star Hike
-- Vans
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80' WHERE id = 24; -- Era Classic
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=800&q=80' WHERE id = 25; -- Sk8-Hi
-- Reebok
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?w=800&q=80' WHERE id = 26; -- Club C 85
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1544923246-77d2c2d5357c?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1544923246-77d2c2d5357c?w=800&q=80' WHERE id = 27; -- Nano X2
-- Asics
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=800&q=80' WHERE id = 28; -- Gel-Nimbus 24
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=800&q=80' WHERE id = 29; -- Gel-Quantum
-- Nike (boots/lifestyle/retro)
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=800&q=80' WHERE id = 30; -- Manoa Boot
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80' WHERE id = 31; -- Adidas Terrex
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?w=800&q=80' WHERE id = 32; -- NB 801 Trail
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80' WHERE id = 33; -- Nike Slip-On
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80' WHERE id = 34; -- Vans Slip-On
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80' WHERE id = 35; -- Air Jordan 4
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=800&q=80' WHERE id = 36; -- Dunk Low Panda
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80' WHERE id = 37; -- Gazelle Bold
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80' WHERE id = 38; -- Handball Spezial
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?w=800&q=80' WHERE id = 39; -- NB 530
UPDATE product SET main_image = 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', main_image_url = 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80' WHERE id = 40; -- NB 327

-- Insert product_image records for products 9-40
INSERT INTO product_image (product_id, image, is_main, sort_order, created_at) VALUES
-- Nike Air Jordan 1 (9)
(9,  'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', 1, 1, @ts),
(9,  'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', 0, 2, @ts),
-- Adidas Stan Smith (10)
(10, 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80', 1, 1, @ts),
(10, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', 0, 2, @ts),
-- Nike Air Force 1 Low White (11)
(11, 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', 1, 1, @ts),
(11, 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=800&q=80', 0, 2, @ts),
-- Nike Air Max 270 React (12)
(12, 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=800&q=80', 1, 1, @ts),
(12, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', 0, 2, @ts),
-- Nike React Infinity Run 3 (13)
(13, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', 1, 1, @ts),
(13, 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', 0, 2, @ts),
-- Nike Air Zoom Pegasus 39 (14)
(14, 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', 1, 1, @ts),
(14, 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=800&q=80', 0, 2, @ts),
-- Adidas NMD R1 (15)
(15, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', 1, 1, @ts),
(15, 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80', 0, 2, @ts),
-- Adidas Yeezy Boost 350 V2 (16)
(16, 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80', 1, 1, @ts),
(16, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', 0, 2, @ts),
-- Adidas Forum Low (17)
(17, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', 1, 1, @ts),
(17, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?w=800&q=80', 0, 2, @ts),
-- New Balance 990v5 (18)
(18, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?w=800&q=80', 1, 1, @ts),
(18, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', 0, 2, @ts),
-- New Balance 2002R (19)
(19, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', 1, 1, @ts),
(19, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?w=800&q=80', 0, 2, @ts),
-- Puma Suede Classic (20)
(20, 'https://images.unsplash.com/photo-1556906781-9a412961c28c?w=800&q=80', 1, 1, @ts),
(20, 'https://images.unsplash.com/photo-1543508282-6319a3e2621f?w=800&q=80', 0, 2, @ts),
-- Puma RS-X Puzzle (21)
(21, 'https://images.unsplash.com/photo-1543508282-6319a3e2621f?w=800&q=80', 1, 1, @ts),
(21, 'https://images.unsplash.com/photo-1556906781-9a412961c28c?w=800&q=80', 0, 2, @ts),
-- Converse All Star Hi Black (22)
(22, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', 1, 1, @ts),
(22, 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800&q=80', 0, 2, @ts),
-- Converse Run Star Hike (23)
(23, 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=800&q=80', 1, 1, @ts),
(23, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&q=80', 0, 2, @ts),
-- Vans Era Classic (24)
(24, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', 1, 1, @ts),
(24, 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=800&q=80', 0, 2, @ts),
-- Vans Sk8-Hi (25)
(25, 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=800&q=80', 1, 1, @ts),
(25, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', 0, 2, @ts),
-- Reebok Club C 85 (26)
(26, 'https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?w=800&q=80', 1, 1, @ts),
(26, 'https://images.unsplash.com/photo-1544923246-77d2c2d5357c?w=800&q=80', 0, 2, @ts),
-- Reebok Nano X2 (27)
(27, 'https://images.unsplash.com/photo-1544923246-77d2c2d5357c?w=800&q=80', 1, 1, @ts),
(27, 'https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?w=800&q=80', 0, 2, @ts),
-- Asics Gel-Nimbus 24 (28)
(28, 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=800&q=80', 1, 1, @ts),
(28, 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=800&q=80', 0, 2, @ts),
-- Asics Gel-Quantum 180 (29)
(29, 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=800&q=80', 1, 1, @ts),
(29, 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=800&q=80', 0, 2, @ts),
-- Nike Manoa Leather Boot (30)
(30, 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=800&q=80', 1, 1, @ts),
(30, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', 0, 2, @ts),
-- Adidas Terrex Swift R3 (31)
(31, 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80', 1, 1, @ts),
(31, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', 0, 2, @ts),
-- New Balance 801 Trail (32)
(32, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?w=800&q=80', 1, 1, @ts),
(32, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', 0, 2, @ts),
-- Nike Slip-On Casual (33)
(33, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', 1, 1, @ts),
(33, 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', 0, 2, @ts),
-- Vans Slip-On Pro Black (34)
(34, 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=800&q=80', 1, 1, @ts),
(34, 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=800&q=80', 0, 2, @ts),
-- Nike Air Jordan 4 Retro (35)
(35, 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', 1, 1, @ts),
(35, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80', 0, 2, @ts),
-- Nike Dunk Low Panda (36)
(36, 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=800&q=80', 1, 1, @ts),
(36, 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80', 0, 2, @ts),
-- Adidas Gazelle Bold (37)
(37, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', 1, 1, @ts),
(37, 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80', 0, 2, @ts),
-- Adidas Handball Spezial (38)
(38, 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80', 1, 1, @ts),
(38, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', 0, 2, @ts),
-- New Balance 530 White Silver (39)
(39, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?w=800&q=80', 1, 1, @ts),
(39, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', 0, 2, @ts),
-- New Balance 327 Moonbeam (40)
(40, 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80', 1, 1, @ts),
(40, 'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?w=800&q=80', 0, 2, @ts);
