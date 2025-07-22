-- Add promo code and discount columns to orders table
ALTER TABLE `orders` 
ADD COLUMN `promo_code` VARCHAR(50) NULL AFTER `payment_method`,
ADD COLUMN `promo_discount` INT(11) DEFAULT 0 COMMENT 'Discount amount in paise' AFTER `promo_code`,
ADD INDEX `idx_promo_code` (`promo_code`);

-- Add foreign key to promo_codes table
ALTER TABLE `orders`
ADD CONSTRAINT `fk_orders_promo_codes` 
FOREIGN KEY (`promo_code`) REFERENCES `promo_codes`(`code`) 
ON DELETE SET NULL ON UPDATE CASCADE;
