<?php
// resend_verification.php - Resend verification email
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (empty($email)) {
        echo json_encode(['error' => 'Please provide your email address.']);
        exit;
    }

    // Check if user exists and is not verified
    $sql = "SELECT user_id, first_name, last_name, is_verified, verification_token, token_expires_at 
            FROM users WHERE email = ? LIMIT 1";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['is_verified']) {
                echo json_encode(['error' => 'This email address is already verified. You can log in normally.']);
                $stmt->close();
                exit;
            }

            // Check if we need to generate a new token (if expired or doesn't exist)
            $needNewToken = false;
            if (!$user['verification_token'] || 
                !$user['token_expires_at'] || 
                strtotime($user['token_expires_at']) < time()) {
                $needNewToken = true;
            }

            if ($needNewToken) {
                // Generate new verification token
                $verificationToken = bin2hex(random_bytes(32));
                $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

                // Update user with new token
                $updateSql = "UPDATE users SET verification_token = ?, token_expires_at = ? WHERE user_id = ?";
                if ($updateStmt = $conn->prepare($updateSql)) {
                    $updateStmt->bind_param("ssi", $verificationToken, $tokenExpires, $user['user_id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                } else {
                    echo json_encode(['error' => 'Database error. Please try again.']);
                    $stmt->close();
                    exit;
                }
            } else {
                $verificationToken = $user['verification_token'];
            }

            // Send verification email
            $userName = $user['first_name'] . ' ' . $user['last_name'];
            if (sendVerificationEmail($email, $userName, $verificationToken)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Verification email sent successfully! Please check your email and click the verification link.'
                ]);
            } else {
                echo json_encode(['error' => 'Failed to send verification email. Please try again later.']);
            }
        } else {
            echo json_encode(['error' => 'No account found with this email address.']);
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

        // SMTP Configuration
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
        $mail->Subject = 'OZYDE - Verify Your Email Address';
        $mail->Body = $emailBody;

        // Plain text version
        $mail->AltBody = "Hi $name,\n\n"
            . "Please verify your email address by visiting:\n\n"
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