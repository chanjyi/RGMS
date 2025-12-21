<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit();
}

$message = "";
$error = "";

if (isset($_POST['update_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    $email = $_SESSION['email'];

    if ($new_pass !== $confirm_pass) {
        $error = "New passwords do not match!";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($current_pass, $user['password'])) {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update->bind_param("ss", $new_hash, $email);
            
            if ($update->execute()) {
                $message = "Password updated successfully!";
            } else {
                $error = "Database error. Try again.";
            }
        } else {
            $error = "Incorrect current password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | RGMS</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <section class="home-section">
        <h1>Settings</h1>
        
        <div class="settings-container">
            <?php if($message): ?>
                <div class="alert success"><?= $message ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>

            <h3 class="section-title">Account Security</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" name="update_password" class="btn-save">Update Password</button>
            </form>

            <br><br>

            <h3 class="section-title">Appearance</h3>
            <div class="form-group" style="display: flex; align-items: center; justify-content: space-between;">
                <label style="margin:0;">High Contrast Mode</label>
                <label class="switch">
                    <input type="checkbox" id="darkModeToggle">
                    <span class="slider"></span>
                </label>
            </div>

            <br><br>

            <?php if ($_SESSION['role'] === 'admin'): ?>
                <h3 class="section-title">System Settings (Admin Only)</h3>
                <div class="form-group">
                    <label>Current Academic Session</label>
                    <input type="text" value="2025/2026" readonly style="cursor: not-allowed; opacity: 0.7;">
                    <small>Only adjustable during system maintenance.</small>
                </div>
            <?php endif; ?>

        </div>
    </div>
    
    <script>
        // DARK/HIGH CONTRAST MODE LOGIC
        const toggle = document.getElementById('darkModeToggle');
        const body = document.body;

        if (localStorage.getItem('darkMode') === 'enabled') {
            body.classList.add('dark-mode');
            toggle.checked = true;
        }

        toggle.addEventListener('change', () => {
            if (toggle.checked) {
                body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    </script>
</body>
</html>