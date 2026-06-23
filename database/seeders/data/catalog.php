<?php

declare(strict_types=1);

/*
| Curated demo catalogue. Each entry is [vendor store, leaf category, name,
| price in dollars, image keyword]. Variants (sizes/colours) are generated per
| category by ProductSeeder. Products are themed to a matching vendor.
*/

return [
    // ---- Nordic Threads — Apparel -----------------------------------------
    ['Nordic Threads', "Men's Clothing", 'Merino Wool Crewneck Sweater', 89.00, 'sweater'],
    ['Nordic Threads', "Men's Clothing", 'Oxford Button-Down Shirt', 59.00, 'shirt'],
    ['Nordic Threads', "Men's Clothing", 'Slim-Fit Chino Trousers', 69.00, 'chinos,trousers'],
    ['Nordic Threads', "Women's Clothing", 'Linen Wrap Midi Dress', 98.00, 'dress'],
    ['Nordic Threads', "Women's Clothing", 'Cropped Denim Jacket', 79.00, 'denim,jacket'],
    ['Nordic Threads', "Women's Clothing", 'Ribbed Knit Cardigan', 72.00, 'cardigan'],
    ['Nordic Threads', 'Footwear', 'Leather Chelsea Boots', 149.00, 'boots'],
    ['Nordic Threads', 'Footwear', 'Canvas Low-Top Sneakers', 65.00, 'sneakers'],
    ['Nordic Threads', 'Accessories', 'Full-Grain Leather Belt', 45.00, 'leather,belt'],
    ['Nordic Threads', 'Accessories', 'Cashmere Scarf', 55.00, 'scarf'],

    // ---- Volt Electronics — Electronics -----------------------------------
    ['Volt Electronics', 'Phones', 'Aurora 5G Smartphone', 699.00, 'smartphone'],
    ['Volt Electronics', 'Phones', 'Nova Compact Phone', 549.00, 'smartphone,phone'],
    ['Volt Electronics', 'Laptops', 'UltraBook 14 Slim Laptop', 1199.00, 'laptop'],
    ['Volt Electronics', 'Laptops', 'Creator Pro 16 Laptop', 1899.00, 'laptop,computer'],
    ['Volt Electronics', 'Audio', 'Noise-Cancelling Headphones', 279.00, 'headphones'],
    ['Volt Electronics', 'Audio', 'Wireless Earbuds Pro', 149.00, 'earbuds'],
    ['Volt Electronics', 'Audio', 'Portable Bluetooth Speaker', 89.00, 'speaker'],
    ['Volt Electronics', 'Wearables', 'FitTrack Smartwatch', 199.00, 'smartwatch'],
    ['Volt Electronics', 'Wearables', 'Sport GPS Watch', 249.00, 'watch'],

    // ---- Hearth & Home — Home & Kitchen -----------------------------------
    ['Hearth & Home', 'Cookware', 'Cast Iron Skillet', 39.00, 'skillet,pan'],
    ['Hearth & Home', 'Cookware', 'Ceramic Dutch Oven', 119.00, 'dutch,oven,pot'],
    ['Hearth & Home', 'Furniture', 'Oak Bedside Table', 149.00, 'nightstand,table'],
    ['Hearth & Home', 'Furniture', 'Velvet Accent Chair', 229.00, 'armchair,chair'],
    ['Hearth & Home', 'Decor', 'Stoneware Table Vase', 34.00, 'vase'],
    ['Hearth & Home', 'Decor', 'Brass Table Lamp', 79.00, 'lamp'],
    ['Hearth & Home', 'Appliances', 'Espresso Machine', 349.00, 'espresso,machine'],
    ['Hearth & Home', 'Appliances', 'Compact Air Fryer', 99.00, 'air,fryer'],

    // ---- PageTurner Books — Books -----------------------------------------
    ['PageTurner Books', 'Fiction', 'The Midnight Library', 16.00, 'book,novel'],
    ['PageTurner Books', 'Fiction', 'Dune', 14.00, 'book'],
    ['PageTurner Books', 'Fiction', 'Project Hail Mary', 18.00, 'book,reading'],
    ['PageTurner Books', 'Non-Fiction', 'Atomic Habits', 20.00, 'book,desk'],
    ['PageTurner Books', 'Non-Fiction', 'Sapiens: A Brief History', 22.00, 'book,library'],
    ['PageTurner Books', "Children's", 'The Very Hungry Caterpillar', 12.00, 'childrens,book'],
    ['PageTurner Books', "Children's", 'Where the Wild Things Are', 13.00, 'picture,book'],

    // ---- Peak Outdoors — Sports & Outdoors --------------------------------
    ['Peak Outdoors', 'Fitness', 'Adjustable Dumbbell Set', 199.00, 'dumbbell'],
    ['Peak Outdoors', 'Fitness', 'Cork Yoga Mat', 49.00, 'yoga,mat'],
    ['Peak Outdoors', 'Fitness', 'Resistance Band Kit', 29.00, 'resistance,band'],
    ['Peak Outdoors', 'Camping', '2-Person Backpacking Tent', 189.00, 'tent'],
    ['Peak Outdoors', 'Camping', 'Down Sleeping Bag', 129.00, 'sleeping,bag'],
    ['Peak Outdoors', 'Camping', 'Trail Camping Stove', 59.00, 'camping,stove'],
    ['Peak Outdoors', 'Cycling', 'Carbon Road Bike Helmet', 119.00, 'bike,helmet'],
    ['Peak Outdoors', 'Cycling', 'LED Bike Light Set', 35.00, 'bike,light'],
    ['Peak Outdoors', 'Cycling', 'Breathable Cycling Jersey', 65.00, 'cycling,jersey'],
];
