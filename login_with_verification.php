<?php
// login_with_verification.php - Enhanced login with email verification check
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        echo json_encode(['error' => 'Please enter both email and password.']);
        exit;
    }

    // Check user credentials and verification status
    $sql = "SELECT user_id, first_name, last_name, email, password, is_verified, role 
            FROM users WHERE email = ? LIMIT 1";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                
                // Check if email is verified
                if (!$user['is_verified']) {
                    echo json_encode([
                        'error' => 'Your email address has not been verified yet. Please check your email and click the verification link before logging in.',
                        'unverified' => true,
                        'email' => $user['email']
                    ]);
                    $stmt->close();
                    exit;
                }
                
                // Login successful - create session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['is_verified'] = true;
                
                // Determine redirect based on role
                $redirectUrl = ($user['role'] === 'admin') ? 'admindashboard.html' : 'catalog.php';
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful!',
                    'redirect' => $redirectUrl,
                    'user_name' => $user['first_name'] . ' ' . $user['last_name']
                ]);
                
            } else {
                echo json_encode(['error' => 'Invalid email or password.']);
            }
        } else {
            echo json_encode(['error' => 'Invalid email or password.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['error' => 'Database error. Please try again.']);
    }
    $conn->close();
}
?>