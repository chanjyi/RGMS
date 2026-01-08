<?php
session_start();
require 'config.php';

$msg = "";

if (isset($_POST['reset_password'])) {
    $email = trim($_POST['email']);
    $new_pass = $_POST['new_password'];

    // Check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        // Update Password
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update->bind_param("ss", $hash, $email);
        
        if ($update->execute()) {
            $msg = "<div class='alert success'>Password updated successfully! <a href='index.php' style='color: inherit; font-weight: bold;'>Login here</a></div>";
        } else {
            $msg = "<div class='alert error'>Error updating password.</div>";
        }
    } else {
        $msg = "<div class='alert error'>Email not found in our system.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
    
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <style>
        /* Simple centering for this standalone page */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            /* background color is already handled by style.css (#DFD0B8) */
        }
    </style>
</head>
<body>

    <div class="form-box" style="width: 100%; max-width: 400px; text-align: center;">
        
        <h2 style="color: #3C5B6F; margin-bottom: 20px;">
            <i class='bx bx-lock-open-alt'></i> Reset Password
        </h2>
        
        <?= $msg ?>

        <form method="post">
            <div class="input-group">
                <label style="text-align: left;">Email Address</label>
                <input type="email" name="email" required placeholder="Enter your registered email">
            </div>
            
            <div class="input-group">
                <label style="text-align: left;">New Password</label>
                <input type="password" name="new_password" required placeholder="Enter new password">
            </div>

            <button type="submit" name="reset_password" class="btn-save" style="width: 100%; margin-top: 10px;">
                Update Password
            </button>
            
            <div style="margin-top: 20px;">
                <a href="index.php" class="btn-back" style="justify-content: center;">
                    <i class='bx bx-arrow-back'></i> Back to Login
                </a>
            </div>
        </form>
    </div>

</body>
</html>