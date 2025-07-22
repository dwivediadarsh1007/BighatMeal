-- Add Healthy Salad Bowl Category
INSERT INTO categories (name, description) VALUES 
('Healthy Salad Bowl', 'Fresh, nutritious fruit bowls designed for your daily wellness needs');

-- Get the category ID for Healthy Salad Bowl
SET @healthy_bowl_id = LAST_INSERT_ID();

-- Add Weekly Rotating Fruit Bowl Plan
-- Day 1 - Skin & Glow Boost
INSERT INTO products (category_id, name, description, price) VALUES 
(@healthy_bowl_id, 'Skin & Glow Boost Bowl - Monday', 
'Vitamin C + hydration + antioxidants bowl with Orange/Mausambi, Kiwi/Amla, Pomegranate, Cucumber slices, Mint & lemon zest. Next week variants: Strawberry, Red Grapes, Dragon Fruit', 
199);

-- Day 2 - Energy & Strength
INSERT INTO products (category_id, name, description, price) VALUES 
(@healthy_bowl_id, 'Energy & Strength Bowl - Tuesday', 
'Iron, potassium, natural sugars bowl with Banana, Dates, Apple, Musk melon, Chia seed sprinkle. Next week variants: Sweet lime, Fig, Pear, Coconut chips', 
229);

-- Day 3 - Digestion & Gut Cleanse
INSERT INTO products (category_id, name, description, price) VALUES 
(@healthy_bowl_id, 'Digestion & Gut Cleanse Bowl - Wednesday', 
'Fiber, enzymes bowl with Papaya, Apple (with peel), Pineapple, Soaked raisins, Jeera/saunf sprinkle. Next week variants: Guava, Prunes, Cucumber + Beetroot', 
219);

-- Day 4 - Immunity & Detox
INSERT INTO products (category_id, name, description, price) VALUES 
(@healthy_bowl_id, 'Immunity & Detox Bowl - Thursday', 
'Antioxidants bowl with Amla/Gooseberry Candy, Blueberries/Black Grapes, Orange, Watermelon, Basil seed. Next week variants: Jamun, Passion fruit, Bael', 
249);

-- Day 5 - Muscle & Mind Booster
INSERT INTO products (category_id, name, description, price) VALUES 
(@healthy_bowl_id, 'Muscle & Mind Booster Bowl - Friday', 
'Magnesium, protein bowl with Banana, Avocado (optional), Chopped nuts, Dates/Anjeer, Apple or Pear. Next week variants: Lychee, Chikoo, Apricot + Sunflower seeds', 
269);

-- Weekend Fusion Bowl
INSERT INTO products (category_id, name, description, price) VALUES 
(@healthy_bowl_id, 'Weekend Fusion Bowl - Saturday', 
'Assorted fresh fruits bowl with Watermelon, Papaya, Apple, Raisins, Lemon honey drizzle', 
299);

INSERT INTO products (category_id, name, description, price) VALUES 
(@healthy_bowl_id, 'Weekend Fusion Bowl - Sunday', 
'Special weekend bowl with Mango cubes, Banana, Berries, Tulsi water shot', 
329);
