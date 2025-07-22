<?php
require_once 'config.php';

// Insert sample categories
$categories = [
    ['name' => 'Pizza', 'description' => 'Delicious pizza options', 'image_url' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=200'],
    ['name' => 'Burgers', 'description' => 'Juicy burgers with various toppings', 'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200'],
    ['name' => 'Pasta', 'description' => 'Italian pasta dishes', 'image_url' => 'https://images.unsplash.com/photo-1563453091183-7918470c9fc7?w=200'],
    ['name' => 'Sushi', 'description' => 'Fresh sushi rolls', 'image_url' => 'https://images.unsplash.com/photo-1546832940-d106c494d14d?w=200'],
    ['name' => 'Desserts', 'description' => 'Sweet treats and desserts', 'image_url' => 'https://images.unsplash.com/photo-1551024684-03a689d186c9?w=200'],
    ['name' => 'Drinks', 'description' => 'Beverages and drinks', 'image_url' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=200']
];

foreach ($categories as $category) {
    $stmt = $conn->prepare("INSERT INTO categories (name, description, image_url) VALUES (?, ?, ?)");
    $stmt->execute([$category['name'], $category['description'], $category['image_url']]);
}

// Insert sample products
$products = [
    [
        'name' => 'Margherita Pizza',
        'description' => 'Classic Italian pizza with fresh tomatoes, mozzarella, and basil',
        'price' => 12.99,
        'image_url' => 'https://images.unsplash.com/photo-1513104890138-7c74960c9fc1?w=400',
        'category_id' => 1
    ],
    [
        'name' => 'Classic Burger',
        'description' => 'Juicy beef patty with lettuce, tomato, and special sauce',
        'price' => 8.99,
        'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400',
        'category_id' => 2
    ],
    [
        'name' => 'Spaghetti Carbonara',
        'description' => 'Creamy pasta with pancetta and parmesan',
        'price' => 14.99,
        'image_url' => 'https://images.unsplash.com/photo-1563453091183-7918470c9fc7?w=400',
        'category_id' => 3
    ],
    [
        'name' => 'California Roll',
        'description' => 'Classic sushi roll with crab, avocado, and cucumber',
        'price' => 10.99,
        'image_url' => 'https://images.unsplash.com/photo-1546832940-d106c494d14d?w=400',
        'category_id' => 4
    ],
    [
        'name' => 'Chocolate Lava Cake',
        'description' => 'Warm chocolate cake with gooey center',
        'price' => 6.99,
        'image_url' => 'https://images.unsplash.com/photo-1551024684-03a689d186c9?w=400',
        'category_id' => 5
    ],
    [
        'name' => 'Iced Latte',
        'description' => 'Freshly brewed coffee with milk and ice',
        'price' => 4.99,
        'image_url' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=400',
        'category_id' => 6
    ]
];

foreach ($products as $product) {
    $stmt = $conn->prepare("
        INSERT INTO products (name, description, price, image_url, category_id) 
        VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $product['name'],
        $product['description'],
        $product['price'],
        $product['image_url'],
        $product['category_id']
    ]);
}

echo "Sample data has been successfully inserted into the database!";
?>
