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
    $username_or_email = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    if (empty($username_or_email)) {
        $errors[] = "Username or email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    if (empty($errors)) {
        // Check if user exists
        $sql = "SELECT * FROM users WHERE (username = '$username_or_email' OR email = '$username_or_email')";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password (no hashing - direct comparison)
            if ($password === $user['password']) {
                // Check if account is active
                if ($user['status'] !== 'active') {
                    $errors[] = "Your account is " . $user['status'] . ". Please contact support.";
                } else {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['user_role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    // Update last login
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $update_sql = "UPDATE users SET last_login = NOW(), ip_address = '$ip_address' WHERE id = " . $user['id'];
                    mysqli_query($conn, $update_sql);
                    
                    // Log activity
                    $action = "User Login";
                    $description = "User logged in: " . $user['username'];
                    mysqli_query($conn, "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                                       VALUES (" . $user['id'] . ", '$action', '$description', '$ip_address')");
                    
                    // Set remember me cookie (simplified)
                    if ($remember) {
                        setcookie('remember_user', $user['username'], time() + (86400 * 30), "/");
                    }
                    
                    // Redirect based on role
                    if ($user['user_role'] === 'admin') {
                        header('Location: admin/dashboard.php');
                    } else {
                        header('Location: dashboard.php');
                    }
                    exit();
                }
            } else {
                $errors[] = "Invalid username or password";
            }
        } else {
            $errors[] = "Invalid username or password";
        }
    }
}

// Check for logout message
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $success = "You have been logged out successfully.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to your SMM Panel account">
    <title>Login - SMM Panel</title>
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
                    <li><a href="index.html">Home</a></li>
                    <li><a href="index.html#services">Services</a></li>
                    <li><a href="index.html#pricing">Pricing</a></li>
                </ul>
                <div class="nav-buttons">
                    <a href="login.php" class="btn-login active">Login</a>
                    <a href="register.php" class="btn-register">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Login Section -->
    <section class="auth-section">
        <div class="container">
            <div class="auth-container" style="grid-template-columns: 500px 1fr; max-width: 1000px;">
                <div class="auth-card">
                    <div class="auth-header">
                        <i class="fas fa-sign-in-alt"></i>
                        <h2>Welcome Back</h2>
                        <p>Login to access your dashboard</p>
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

                    <form method="POST" action="" class="auth-form">
                        <div class="form-group">
                            <label for="username">
                                <i class="fas fa-user"></i> Username or Email
                            </label>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   placeholder="Enter your username or email"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                   required
                                   autofocus>
                        </div>

                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i> Password
                            </label>
                            <div class="password-input">
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Enter your password"
                                       required>
                                <i class="fas fa-eye toggle-password" data-target="password"></i>
                            </div>
                        </div>

                        <div class="form-group" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <label class="checkbox-label" style="margin: 0;">
                                <input type="checkbox" name="remember">
                                <span>Remember me</span>
                            </label>
                            <a href="forgot-password.php" style="color: var(--primary-color); text-decoration: none; font-size: 0.95rem;">
                                Forgot Password?
                            </a>
                        </div>

                        <button type="submit" class="btn-auth">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>

                        <div class="auth-footer">
                            <p>Don't have an account? <a href="register.php">Sign up now</a></p>
                        </div>
                    </form>

                    
                </div>

                <div class="auth-benefits">
                    <h3>Why Choose Us?</h3>
                    <div class="benefit-item">
                        <i class="fas fa-chart-line"></i>
                        <div>
                            <h4>Grow Your Presence</h4>
                            <p>Boost your social media with real, high-quality engagement</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h4>Save Time</h4>
                            <p>Automated delivery system for instant results</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-users"></i>
                        <div>
                            <h4>Trusted by Thousands</h4>
                            <p>Join 10,000+ satisfied customers worldwide</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <i class="fas fa-lock"></i>
                        <div>
                            <h4>Secure & Private</h4>
                            <p>Your data is protected with enterprise-grade security</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="assets/js/auth.js"></script>
</body>
</html>