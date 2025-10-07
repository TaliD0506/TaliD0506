<?php
// setup_email_verification.php - Setup script for email verification system
include 'db.php';

echo "<h2>OZYDE Email Verification System Setup</h2>";
echo "<p>This script will update your database to support email verification.</p>";

try {
    // Check if columns already exist
    $checkColumns = "SHOW COLUMNS FROM users LIKE 'is_verified'";
    $result = $conn->query($checkColumns);
    
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>✓ Email verification columns already exist in the database.</p>";
    } else {
        echo "<h3>Adding email verification columns...</h3>";
        
        // Add email verification columns
        $sql1 = "ALTER TABLE users ADD COLUMN is_verified BOOLEAN DEFAULT FALSE";
        if ($conn->query($sql1) === TRUE) {
            echo "<p style='color: green;'>✓ Added is_verified column</p>";
        } else {
            echo "<p style='color: red;'>✗ Error adding is_verified column: " . $conn->error . "</p>";
        }
        
        $sql2 = "ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL";
        if ($conn->query($sql2) === TRUE) {
            echo "<p style='color: green;'>✓ Added verification_token column</p>";
        } else {
            echo "<p style='color: red;'>✗ Error adding verification_token column: " . $conn->error . "</p>";
        }
        
        $sql3 = "ALTER TABLE users ADD COLUMN token_expires_at TIMESTAMP NULL";
        if ($conn->query($sql3) === TRUE) {
            echo "<p style='color: green;'>✓ Added token_expires_at column</p>";
        } else {
            echo "<p style='color: red;'>✗ Error adding token_expires_at column: " . $conn->error . "</p>";
        }
        
        // Add indexes
        echo "<h3>Adding database indexes...</h3>";
        
        $sql4 = "CREATE INDEX idx_verification_token ON users(verification_token)";
        if ($conn->query($sql4) === TRUE) {
            echo "<p style='color: green;'>✓ Added verification_token index</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Index may already exist: " . $conn->error . "</p>";
        }
        
        $sql5 = "CREATE INDEX idx_email_verified ON users(email, is_verified)";
        if ($conn->query($sql5) === TRUE) {
            echo "<p style='color: green;'>✓ Added email_verified index</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Index may already exist: " . $conn->error . "</p>";
        }
        
        // Mark existing users as verified (optional)
        echo "<h3>Updating existing users...</h3>";
        $sql6 = "UPDATE users SET is_verified = TRUE WHERE verification_token IS NULL";
        if ($conn->query($sql6) === TRUE) {
            $affectedRows = $conn->affected_rows;
            echo "<p style='color: green;'>✓ Marked $affectedRows existing users as verified</p>";
        } else {
            echo "<p style='color: red;'>✗ Error updating existing users: " . $conn->error . "</p>";
        }
    }
    
    // Check PHPMailer installation
    echo "<h3>Checking PHPMailer installation...</h3>";
    if (file_exists('vendor/autoload.php')) {
        echo "<p style='color: green;'>✓ PHPMailer vendor directory exists</p>";
        require 'vendor/autoload.php';
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            echo "<p style='color: green;'>✓ PHPMailer classes are available</p>";
        } else {
            echo "<p style='color: red;'>✗ PHPMailer classes not found. Run 'composer install'</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Vendor directory not found. Run 'composer install' to install PHPMailer</p>";
    }
    
    // Test email template
    echo "<h3>Checking email template...</h3>";
    if (file_exists('email_templates/registration_confirmation.html')) {
        echo "<p style='color: green;'>✓ Email template found</p>";
    } else {
        echo "<p style='color: red;'>✗ Email template not found at email_templates/registration_confirmation.html</p>";
    }
    
    echo "<h3>Setup Complete!</h3>";
    echo "<p>Your database has been updated for email verification. Make sure to:</p>";
    echo "<ul>";
    echo "<li>Run 'composer install' if PHPMailer is not available</li>";
    echo "<li>Test email sending with test_email.php</li>";
    echo "<li>Update your registration form to use register_with_email.php</li>";
    echo "<li>Update your login form to use login_with_verification.php</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h2 {
    color: #333;
    border-bottom: 2px solid #333;
    padding-bottom: 10px;
}
h3 {
    color: #555;
    margin-top: 30px;
}
ul {
    background: #f5f5f5;
    padding: 15px 30px;
    border-radius: 5px;
}
</style>