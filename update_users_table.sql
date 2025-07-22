-- Add google_id column to users table if it doesn't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) DEFAULT NULL,
ADD UNIQUE INDEX IF NOT EXISTS idx_google_id (google_id);

-- Make email unique if not already
ALTER TABLE users 
ADD UNIQUE INDEX IF NOT EXISTS idx_email (email);
