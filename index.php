<?php
session_start();
$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? ''
];
$activeForm = $_SESSION['active_form'] ?? 'login';

session_unset();

function showError($errors) {
   return !empty($errors) ? '<div class="error-message">' . htmlspecialchars($errors) . '</div>' : '';
}

function isActiveForm($formName, $activeForm) {
    return $formName === $activeForm ? 'active' : '';
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Grant System</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- NAVBAR -->
    <header>
        <h2 class="logo">Research Grant System</h2>
        <nav class="navigation">
            <a href="#">Home</a>
            <a href="#">About</a>
            <a href="#">Contact</a>

            <!-- this still opens your login popup -->
            <button class="btnLogin-popup">Login</button>
        </nav>
    </header>

    <!-- HERO SECTION (HOME PAGE) -->
    <main class="hero">
        <div class="hero-text">
            <!-- <p class="badge">Best Coffee</p> -->
            <h1>Manage Research Grants with Confident !</h1>
            <p class="subtitle">
                Comprehensive grant management platform for research institutions. Track funding, manage applications, and collaborate seamlessly.
            </p>

            <div class="hero-buttons">
                <!-- also opens the login popup -->
                <button class="btnLogin-popup hero-btn primary">Start Now</button>
                <a href="#contact" class="hero-btn outline">Contact Us</a>
            </div>
        </div>

        <div class="hero-image">
            <!-- change coffee-cup.png to your image file name -->
            <img src="research.png" alt="Research Grants">
        </div>
    </main>

    <!-- LOGIN & REGISTER POPUP -->
    <div class="wrapper">
        <span class="icon-close"><ion-icon name="close"></ion-icon></span>

        <div class="form-box login <?= isActiveForm('login', $activeForm) ?>">
            <form action="login_register.php" method="post">
            <h2>Login</h2>
            <?= showError($errors['login']) ?>
                <div class="input-box">
                    <span class="icon"><ion-icon name="mail"></ion-icon></span>
                    <input type="email" name="email" required>
                    <label>Email</label>
                </div>
                <div class="input-box">
                    <span class="icon"><ion-icon name="unlock"></ion-icon></span>
                    <input type="password" name="password" required>
                    <label>Password</label>
                </div>
                <div class="remember-forgot">
                    <label><input type="checkbox">Remember me</label>
                    <a href="#">Forgot Password?</a>
                </div>
                <button type="submit" name="login" class="btn">Login</button>
                <div class="login-register">
                    <p>Don't have an account? <a href="#" class="register-link">Register</a></p>
                </div>
            </form>
        </div>

        <div class="form-box register <?= isActiveForm('register', $activeForm) ?>">
            <form action="login_register.php" method="post">
            <h2>Registration</h2>
            <?= showError($errors['register']) ?>
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
                    <span class="icon"><ion-icon name="unlock"></ion-icon></span>
                    <input type="password" name="password" required>
                    <label>Password</label>
                </div>
                <div class="input-box">
                    <span class="icon"><ion-icon name="shield-checkmark"></ion-icon></span>
                    <select name="role" required>
                        <option value="" disabled selected>Select Role</option>
                        <option value="researcher">Researcher</option>
                        <option value="reviewer">Reviewer</option>
                        <option value="hod">HOD</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="remember-forgot">
                    <label><input type="checkbox">I agree the terms & conditions</label>
                </div>
                <button type="submit" name="register" class="btn" >Register</button>
                <div class="login-register">
                    <p>Already have an account? <a href="#" class="login-link">Login</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
    <script type="module"
        src="https://unpkg.com/ionicons@4.5.10-0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule
        src="https://unpkg.com/ionicons@4.5.10-0/dist/ionicons/ionicons.js"></script>

</body>
</html>
