<?php
// register_with_email.php - Enhanced registration with email verification
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['signupEmail']);
    $phone = trim($_POST['phone']);
    $password = $_POST['newPassword'];
    $confirm = $_POST['confirmPassword'];

    // Validate password match
    if ($password !== $confirm) {
        echo json_encode(['error' => 'Passwords do not match.']);
        exit;
    }
    
    // Validate password strength
    if (strlen($password) < 8 || 
        !preg_match("/[0-9]/", $password) || 
        !preg_match("/[!@#$%^&*]/", $password)) {
        echo json_encode(['error' => 'Password must be at least 8 characters, include a number and a special character (!@#$%^&*).']);
        exit;
    }

    // Check if email already exists
    $checkEmail = "SELECT user_id, is_verified FROM users WHERE email = ?";
    if ($stmt = $conn->prepare($checkEmail)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($user['is_verified']) {
                echo json_encode(['error' => 'An account with this email already exists and is verified. Please log in.']);
            } else {
                echo json_encode(['error' => 'An account with this email exists but is not verified. Please check your email for the verification link.']);
            }
            $stmt->close();
            exit;
        }
        $stmt->close();
    }

    // Generate verification token
    $verificationToken = bin2hex(random_bytes(32));
    $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert new user (unverified)
    $sql = "INSERT INTO users (first_name, last_name, email, password, phone, is_verified, verification_token, token_expires_at) 
            VALUES (?, ?, ?, ?, ?, 0, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssssss", $firstName, $lastName, $email, $hashedPassword, $phone, $verificationToken, $tokenExpires);

        if ($stmt->execute()) {
            $newUserId = $conn->insert_id;
            
            // Send verification email
            if (sendVerificationEmail($email, $firstName . ' ' . $lastName, $verificationToken)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Account created successfully! Please check your email to verify your account.',
                    'email' => $email
                ]);
            } else {
                // If email sending fails, still allow registration but notify user
                echo json_encode([
                    'success' => true,
                    'message' => 'Account created successfully! However, there was an issue sending the verification email. Please contact support.',
                    'email_error' => true
                ]);
            }
        } else {
            echo json_encode(['error' => 'Registration failed. Please try again. Error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['error' => 'Database error. Please try again.']);
    }
    $conn->close();
}

function sendVerificationEmail($email, $name, $token) {
    $mail = new PHPMailer(true);

    try {
        // Get email template
        $emailTemplate = file_get_contents('email_templates/registration_confirmation.html');
        
        // Get the current domain for verification link
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $domain = $_SERVER['HTTP_HOST'];
        $verificationLink = $protocol . '://' . $domain . '/verify.php?token=' . $token . '&email=' . urlencode($email);
        
        // Replace placeholders in template
        $emailBody = str_replace([
            '{{USER_NAME}}',
            '{{VERIFICATION_LINK}}'
        ], [
            htmlspecialchars($name),
            $verificationLink
        ], $emailTemplate);

        // SMTP Configuration (using existing credentials from test_email.php)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'shafee.mmadi@gmail.com';
        $mail->Password = 'anug yjfi iorp yhll';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Email settings
        $mail->setFrom('shafee.mmadi@gmail.com', 'OZYDE Boutique');
        $mail->addAddress($email, $name);
        $mail->addReplyTo('ozydedesigns@gmail.com', 'OZYDE Support');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to OZYDE - Please Verify Your Email';
        $mail->Body = $emailBody;

        // Plain text version for clients that don't support HTML
        $mail->AltBody = "Welcome to OZYDE!\n\n"
            . "Hi $name,\n\n"
            . "Thank you for joining OZYDE! To complete your registration, please verify your email address by visiting:\n\n"
            . "$verificationLink\n\n"
            . "This link will expire in 24 hours.\n\n"
            . "Best regards,\nThe OZYDE Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}
?>