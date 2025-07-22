-- Add reset_token and reset_expires columns to users table
ALTER TABLE users
ADD COLUMN reset_token VARCHAR(64) NULL,
ADD COLUMN reset_expires DATETIME NULL,
ADD INDEX idx_reset_token (reset_token);
