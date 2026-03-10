<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smm_panel');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Timezone
date_default_timezone_set('UTC');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    // Configure session for AJAX compatibility
    session_set_cookie_params([
        'lifetime' => 0, // Session cookie
        'path' => '/',
        'domain' => '', // Leave empty for localhost
        'secure' => false, // Set to true for HTTPS
        'httponly' => false, // Allow JavaScript access for AJAX
        'samesite' => 'Lax' // Allow cross-site requests
    ]);
    session_start();
}

// Site Configuration
define('SITE_NAME', 'SMM Panel');
define('SITE_URL', 'http://localhost/smm-panel/');
define('SITE_EMAIL', 'support@smmpanel.com');

// Security
define('ENCRYPTION_KEY', 'your-secret-encryption-key-here'); // Change this!

// Function to sanitize input
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Function to generate random string
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Function to hash password (no hashing — returns raw password)
function hashPassword($password) {
    // NOTE: Storing raw passwords is insecure. Recommended only for testing.
    return $password;
}

// Function to verify password (direct comparison without hashing)
function verifyPassword($password, $hash) {
    // NOTE: Direct comparison of raw passwords; insecure for production.
    return $password === $hash;
}
?>