-- Email Verification Migration for OZYDE Dress Rental System
-- This script adds email verification functionality to the existing users table

-- Add email verification columns to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_verified BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN IF NOT EXISTS verification_token VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS token_expires_at TIMESTAMP NULL;

-- Add index for faster token lookups
CREATE INDEX IF NOT EXISTS idx_verification_token ON users(verification_token);
CREATE INDEX IF NOT EXISTS idx_email_verified ON users(email, is_verified);

-- Optional: Mark existing users as verified (for existing users in the system)
UPDATE users SET is_verified = TRUE WHERE verification_token IS NULL;

-- Create a cleanup procedure for expired tokens (optional)
DELIMITER //
CREATE PROCEDURE CleanExpiredTokens()
BEGIN
    UPDATE users 
    SET verification_token = NULL, token_expires_at = NULL 
    WHERE token_expires_at IS NOT NULL AND token_expires_at < NOW();
END //
DELIMITER ;

-- Comments on new columns:
-- is_verified: Boolean flag indicating if the user's email has been confirmed
-- verification_token: Unique token sent via email for verification
-- token_expires_at: Timestamp when the verification token expires (24 hours from creation)