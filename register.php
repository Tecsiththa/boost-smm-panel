<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // No hashing as requested
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize_input($_POST['full_name']);
    $phone = sanitize_input($_POST['phone']);
    $role = isset($_POST['role']) ? sanitize_input($_POST['role']) : 'user';
    
    // Validation
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    // Check if username exists
    if (empty($errors)) {
        $check_username = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
        if (mysqli_num_rows($check_username) > 0) {
            $errors[] = "Username already exists";
        }
    }
    
    // Check if email exists
    if (empty($errors)) {
        $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check_email) > 0) {
            $errors[] = "Email already registered";
        }
    }
    
    // Validate role
    $allowed_roles = ['user', 'reseller'];
    if (!in_array($role, $allowed_roles)) {
        $role = 'user';
    }
    
    // Insert user if no errors
    if (empty($errors)) {
        $verification_token = generateRandomString(32);
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        $sql = "INSERT INTO users (username, email, password, full_name, phone, user_role, verification_token, ip_address, status) 
                VALUES ('$username', '$email', '$password', '$full_name', '$phone', '$role', '$verification_token', '$ip_address', 'active')";
        
        if (mysqli_query($conn, $sql)) {
            $user_id = mysqli_insert_id($conn);
            
            // Log activity
            $action = "User Registration";
            $description = "New user registered: $username ($email)";
            mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                               VALUES ($user_id, '$action', '$description', '$ip_address')");
            
            $success = "Registration successful! You can now login.";
            
            // Optional: Send verification email here
            
            // Redirect to login after 2 seconds
            header("refresh:2;url=login.php");
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign up for SMM Panel - Boost your social media presence">
    <meta name="keywords" content="SMM Panel, Social Media Marketing, Registration">
    <title>Sign Up - SMM Panel</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-wrapper">
                <div class="logo">
                    <i class="fas fa-rocket"></i>
                    <span>Boost SMM Panel</span>
                </div>
                <ul class="nav-menu">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="index.php#services">Services</a></li>
                    <li><a href="index.php#pricing">Pricing</a></li>
                </ul>
                <div class="nav-buttons">
                    <a href="login.php" class="btn-login">Login</a>
                    <a href="register.php" class="btn-register active">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Registration Section -->
    <section class="auth-section">
        <div class="container">
            <div class="auth-container">
                <div class="auth-card">
                    <div class="auth-header">
                        <i class="fas fa-user-plus"></i>
                        <h2>Create Your Account</h2>
                        <p>Join thousands of satisfied customers growing their social media</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <p><?php echo $success; ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="auth-form" id="registerForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">
                                    <i class="fas fa-user"></i> Username
                                </label>
                                <input type="text" 
                                       id="username" 
                                       name="username" 
                                       placeholder="Choose a username"
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                       required>
                                <small>3-50 characters, letters, numbers, and underscores only</small>
                            </div>

                            <div class="form-group">
                                <label for="full_name">
                                    <i class="fas fa-id-card"></i> Full Name
                                </label>
                                <input type="text" 
                                       id="full_name" 
                                       name="full_name" 
                                       placeholder="Your full name"
                                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                       required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope"></i> Email Address
                                </label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       placeholder="your@email.com"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label for="phone">
                                    <i class="fas fa-phone"></i> Phone Number (Optional)
                                </label>
                                <input type="tel" 
                                       id="phone" 
                                       name="phone" 
                                       placeholder="+1234567890"
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">
                                    <i class="fas fa-lock"></i> Password
                                </label>
                                <div class="password-input">
                                    <input type="password" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Create a strong password"
                                           required>
                                    <i class="fas fa-eye toggle-password" data-target="password"></i>
                                </div>
                                <small>Minimum 6 characters</small>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">
                                    <i class="fas fa-lock"></i> Confirm Password
                                </label>
                                <div class="password-input">
                                    <input type="password" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           placeholder="Confirm your password"
                                           required>
                                    <i class="fas fa-eye toggle-password" data-target="confirm_password"></i>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="role">
                                <i class="fas fa-user-tag"></i> Account Type
                            </label>
                            <select id="role" name="role" class="form-select">
                                <option value="user" <?php echo (isset($_POST['role']) && $_POST['role'] == 'user') ? 'selected' : ''; ?>>
                                    Individual User - For personal use
                                </option>
                                <option value="reseller" <?php echo (isset($_POST['role']) && $_POST['role'] == 'reseller') ? 'selected' : ''; ?>>
                                    Reseller - For business/reselling
                                </option>
                            </select>
                        </div>

                        <div class="form-group checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="terms" required>
                                <span>I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></span>
                            </label>
                        </div>

                        <button type="submit" class="btn-auth">
                            <i class="fas fa-user-plus"></i> Create Account
                        </button>

                        <div class="auth-footer">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </form>
                </div>

                <div class="auth-benefits">
                    <h3>Why Join Us?</h3>
                    <div class="benefit-item">
                        <i class="fas fa-bolt"></i>
                        <div>
                            <h4>Instant Delivery</h4>
                            <p>Get results within seconds of placing your order</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <h4>100% Secure</h4>
                            <p>Your data and account are protected with us</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-dollar-sign"></i>
                        <div>
                            <h4>Best Prices</h4>
                            <p>Competitive pricing with premium quality</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-headset"></i>
                        <div>
                            <h4>24/7 Support</h4>
                            <p>Always here to help you succeed</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="assets/js/auth.js"></script>
</body>
</html>