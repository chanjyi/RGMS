<?php
session_start();

// 1. CAPTURE & CLEAR MESSAGES
$alertMessage = '';
$alertType = ''; // Will be 'success' or 'error'

if (isset($_SESSION['login_error'])) {
    $alertMessage = $_SESSION['login_error'];
    $alertType = 'error'; // Default Red
    unset($_SESSION['login_error']);
} elseif (isset($_SESSION['register_error'])) {
    $alertMessage = $_SESSION['register_error'];
    $alertType = 'error'; // Default Red
    unset($_SESSION['register_error']);
} elseif (isset($_SESSION['message'])) {
    $alertMessage = $_SESSION['message'];
    $alertType = 'success'; // Green (matches your new CSS)
    unset($_SESSION['message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Grant Management System</title>
    <link rel="stylesheet" href="styling/loginpage.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>

    <header>
        <h2 class="logo">Research Grant Management System</h2>
        <nav class="navigation">
            <a href="#">Home</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
            <button class="btnLogin-popup">Login</button>
        </nav>
    </header>

    <?php if ($alertMessage): ?>
    <div class="global-alert <?php echo $alertType; ?>" id="globalAlert">
        <span class="alert-icon">
            <ion-icon name="<?php echo ($alertType === 'success') ? 'checkmark-circle' : 'alert-circle'; ?>"></ion-icon>
        </span>
        <span class="alert-text"><?php echo htmlspecialchars($alertMessage); ?></span>
        <span class="close-btn" onclick="document.getElementById('globalAlert').style.display='none';">&times;</span>
    </div>
    <?php endif; ?>
    <section class="hero">
        <div class="hero-text">
            <h1>Manage Research Grants<br>Confidently !</h1>
            <p class="subtitle">Comprehensive grant management platform for research institutions. Track funding, manage applications, and collaborate seamlessly.</p>
            
            <div class="hero-buttons">
                <button class="hero-btn primary btnLogin-popup-hero">Get Started</button>
                <a href="#about" class="hero-btn outline">Learn More</a>
            </div>
        </div>
    </section>

    <section id="about" class="content-section">
        <h2>About Us</h2>
        <p>
            The Research Grant Management System (RGMS) is designed to help institutions manage the complex lifecycle of research grants. 
            From initial proposal submission to final review and funding allocation, our system ensures transparency, efficiency, and ease of use for Researchers, Reviewers, and Administrators.
        </p>
    </section>

    <section id="contact" class="content-section">
        <h2>Contact Us</h2>
        <p>
            Have questions or need support? Reach out to our administrative team at <strong>admin@rgms.edu</strong> or call us at <strong>+123-456-7890</strong>.
            We are available Monday through Friday, 9:00 AM to 5:00 PM.
        </p>
    </section>

    <div class="wrapper">
        <span class="icon-close"><ion-icon name="close"></ion-icon></span>

        <div class="form-box login">
            <h2>Login</h2>
            <form action="login_register.php" method="POST">
                
                <div class="input-box">
                    <span class="icon"><ion-icon name="mail"></ion-icon></span>
                    <input type="email" name="email" required 
                           value="<?php echo isset($_COOKIE['user_email']) ? htmlspecialchars($_COOKIE['user_email']) : ''; ?>">
                    <label>Email</label>
                </div>

                <div class="input-box">
                    <span class="icon" onclick="togglePassword(this)">
                        <ion-icon name="eye-off"></ion-icon>
                    </span>
                    <input type="password" name="password" required>
                    <label>Password</label>
                </div>

                <div class="remember-forgot">
                    <label>
                        <input type="checkbox" name="remember_me" 
                        <?php echo isset($_COOKIE['user_email']) ? 'checked' : ''; ?>>
                        Remember me
                    </label>
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>

                <button type="submit" name="login" class="btn">Login</button>

                <div class="login-register">
                    <p>Don't have an account? <a href="#" class="register-link">Register</a></p>
                </div>
            </form>
        </div>

        <div class="form-box register">
            <h2>Registration</h2>
            <form action="login_register.php" method="POST">
                
                <div class="input-box">
                    <span class="icon"><ion-icon name="person"></ion-icon></span>
                    <input type="text" name="name" required>
                    <label>Username</label>
                </div>

                <div class="input-box">
                    <span class="icon"><ion-icon name="mail"></ion-icon></span>
                    <input type="email" name="email" required>
                    <label>Email</label>
                </div>

                <div class="input-box">
                    <span class="icon" onclick="togglePassword(this)">
                        <ion-icon name="eye-off"></ion-icon>
                    </span>
                    <input type="password" name="password" required>
                    <label>Password</label>
                </div>

                <div class="input-box">
                    <span class="icon" style="pointer-events:none;"><ion-icon name="briefcase"></ion-icon></span>
                    <select name="role" required>
                        <option value="" disabled selected hidden>Select Role</option>
                        <option value="researcher">Researcher</option>
                        <option value="reviewer">Reviewer</option>
                        <option value="hod">HOD</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="remember-forgot">
                    <label><input type="checkbox" required> I agree to the terms & conditions</label>
                </div>

                <button type="submit" name="register" class="btn">Register</button>

                <div class="login-register">
                    <p>Already have an account? <a href="#" class="login-link">Login</a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        const wrapper = document.querySelector('.wrapper');
        const loginLink = document.querySelector('.login-link');
        const registerLink = document.querySelector('.register-link');
        const btnPopup = document.querySelector('.btnLogin-popup');
        const btnPopupHero = document.querySelector('.btnLogin-popup-hero'); 
        const iconClose = document.querySelector('.icon-close');
        
        // Switch to Register Form
        registerLink.addEventListener('click', () => {
            wrapper.classList.add('active');
        });

        // Switch back to Login Form
        loginLink.addEventListener('click', () => {
            wrapper.classList.remove('active');
        });

        // Open Popup (Navbar)
        btnPopup.addEventListener('click', () => {
            wrapper.classList.add('active-popup');
        });

        // Open Popup (Hero Button)
        if(btnPopupHero) {
            btnPopupHero.addEventListener('click', () => {
                wrapper.classList.add('active-popup');
            });
        }

        // Close Popup
        iconClose.addEventListener('click', () => {
            wrapper.classList.remove('active-popup');
        });

        // Toggle Password Visibility
        function togglePassword(iconSpan) {
            const input = iconSpan.parentElement.querySelector('input');
            const icon = iconSpan.querySelector('ion-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('name', 'eye');
            } else {
                input.type = 'password';
                icon.setAttribute('name', 'eye-off');
            }
        }
    </script>
</body>
</html>