-- Create addresses table if it doesn't exist
CREATE TABLE IF NOT EXISTS addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add foreign key constraint if it doesn't exist
SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.table_constraints 
    WHERE constraint_schema = DATABASE() 
    AND table_name = 'addresses' 
    AND constraint_name = 'fk_addresses_users');

SET @sql = IF(@constraint_exists = 0, 
    'ALTER TABLE addresses ADD CONSTRAINT fk_addresses_users FOREIGN KEY (user_id) REFERENCES users(id)', 
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add sample address for testing
INSERT IGNORE INTO addresses (user_id, address_line1, address_line2, city, state, pincode, phone, is_default) VALUES
(1, '123 Main Street', 'Apt 4B', 'Mumbai', 'Maharashtra', '400001', '9876543210', TRUE);

-- Update orders table structure
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS address_id INT,
ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10,2) NOT NULL,
ADD COLUMN IF NOT EXISTS status ENUM('pending', 'processing', 'delivered', 'cancelled') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS payment_method ENUM('cash', 'card', 'online') DEFAULT 'cash',
ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add foreign key constraints for orders table
SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.table_constraints 
    WHERE constraint_schema = DATABASE() 
    AND table_name = 'orders' 
    AND constraint_name = 'fk_orders_users');

SET @sql = IF(@constraint_exists = 0, 
    'ALTER TABLE orders ADD CONSTRAINT fk_orders_users FOREIGN KEY (user_id) REFERENCES users(id)', 
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.table_constraints 
    WHERE constraint_schema = DATABASE() 
    AND table_name = 'orders' 
    AND constraint_name = 'fk_orders_addresses');

SET @sql = IF(@constraint_exists = 0, 
    'ALTER TABLE orders ADD CONSTRAINT fk_orders_addresses FOREIGN KEY (address_id) REFERENCES addresses(id)', 
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update order_items table structure
ALTER TABLE order_items 
MODIFY COLUMN quantity INT NOT NULL,
MODIFY COLUMN price DECIMAL(10,2) NOT NULL,
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
