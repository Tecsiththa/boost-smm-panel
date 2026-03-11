<?php
require_once 'config.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Log activity
    $action = "User Logout";
    $description = "User logged out";
    mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                       VALUES ($user_id, '$action', '$description', '$ip_address')");
    
    // Delete remember me cookie if exists
    if (isset($_COOKIE['remember_user'])) {
        setcookie('remember_user', '', time() - 3600, "/");
    }
    
    // Clear session
    session_unset();
    session_destroy();
}

// Redirect to login with success message
header('Location: login.php?logout=success');
exit();
?>