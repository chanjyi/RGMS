<?php
session_start();
require 'config.php';

if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit();
}

$message = "";
$error = "";
$view = $_GET['view'] ?? 'menu';
$email = $_SESSION['email']; 
$role = $_SESSION['role'] ?? 'researcher'; // Get User Role

// ==========================================
// 1. LOGIC: UPDATE PROFILE
// ==========================================
if (isset($_POST['update_profile'])) {
    $new_name = trim($_POST['name']); 
    $selected_avatar = $_POST['avatar_option']; 

    if (!empty($new_name) && !empty($selected_avatar)) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, profile_pic = ? WHERE email = ?");
        $stmt->bind_param("sss", $new_name, $selected_avatar, $email);
        
        if ($stmt->execute()) {
            $_SESSION['name'] = $new_name;      
            $_SESSION['profile_pic'] = $selected_avatar;
            $message = "Profile updated successfully!";
            $view = 'profile'; 
        } else {
            $error = "Database update failed.";
            $view = 'profile';
        }
    } else {
        $error = "Please fill in all fields.";
        $view = 'profile';
    }
}

// ==========================================
// 2. LOGIC: CHANGE PASSWORD
// ==========================================
if (isset($_POST['update_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass     = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && !password_verify($current_pass, $user['password'])) {
        $error = "Incorrect current password.";
        $view  = 'security';
    } elseif ($new_pass !== $confirm_pass) {
        $error = "New passwords do not match!";
        $view  = 'security';
    } else {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $update   = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update->bind_param("ss", $new_hash, $email);
        
        if ($update->execute()) {
            $message = "Password updated successfully!";
            $view    = 'security';
        } else {
            $error = "Database error. Try again.";
            $view  = 'security';
        }
    }
}

// ==========================================
// 3. LOGIC: CHANGE EMAIL
// ==========================================
if (isset($_POST['update_email'])) {
    $current_pass = $_POST['password_check']; 
    $new_email    = trim($_POST['new_email']);

    $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && !password_verify($current_pass, $user['password'])) {
        $error = "Incorrect password. Email update cancelled.";
        $view  = 'security'; 
    } else {
        $check = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check->bind_param("s", $new_email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "This email is already registered by another user.";
            $view  = 'security';
        } else {
            $update = $conn->prepare("UPDATE users SET email = ? WHERE email = ?");
            $update->bind_param("ss", $new_email, $email);

            if ($update->execute()) {
                $_SESSION['email'] = $new_email; 
                $email = $new_email; 
                $message = "Email updated successfully!";
                $view = 'security';
            } else {
                $error = "Database error. Try again.";
                $view = 'security';
            }
        }
    }
}

// ==========================================
// 4. LOGIC: NOTIFICATIONS (UPDATED - MASTER TOGGLE)
// ==========================================
if (isset($_POST['update_notifications'])) {
    
    // Check if master toggle is ON (1) or OFF (0)
    $notify_all = isset($_POST['notify_all']) ? 1 : 0;

    // Update ALL notification columns to match the master choice
    $stmt = $conn->prepare("
        UPDATE users 
        SET notify_system = ?, 
            notify_email = ?,
            notify_new_assign = ?, 
            notify_appeals = ?, 
            notify_hod_approve = ?, 
            notify_hod_reject = ? 
        WHERE email = ?
    ");
    
    $stmt->bind_param("iiiiiis", 
        $notify_all, $notify_all, $notify_all, $notify_all, $notify_all, $notify_all, 
        $email
    );

    if ($stmt->execute()) {
        $message = "Notification preferences saved!";
        $view = 'notifications';
    } else {
        $error = "Failed to save settings.";
        $view = 'notifications';
    }
}

// ==========================================
// FINAL: FETCH USER DATA
// ==========================================
$query = "SELECT name, profile_pic, notify_system FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Set Defaults
$current_pic = !empty($user_data['profile_pic']) ? $user_data['profile_pic'] : 'default.png';
$current_name = $user_data['name'] ?? 'User';

// Notification Value (Master)
$is_notif_on = $user_data['notify_system'] ?? 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings | RGMS</title>
    <link rel="stylesheet" href="styling/style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <section class="home-section">
        <div class="welcome-text">Settings</div>
        <hr style="border: 1px solid #3C5B6F; opacity: 0.3; margin: 20px 0;">
        
        <div class="form-box" style="max-width: 800px; margin: 0 auto;">

            <?php if ($message): ?>
                <div class="alert success" style="background:#d4edda; color:#155724; padding:15px; border-radius:10px; margin-bottom:20px;"><?= $message ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert error" style="background:#f8d7da; color:#721c24; padding:15px; border-radius:10px; margin-bottom:20px;"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($view === 'menu'): ?>
                <p style="margin-bottom: 30px;">Manage your account preferences.</p>

                <a href="settings.php?view=profile" class="settings-nav-btn">
                    <span>Edit Profile</span>
                    <i class='bx bx-user'></i>
                </a>

                <a href="settings.php?view=security" class="settings-nav-btn">
                    <span>Account Security</span>
                    <i class='bx bx-shield-quarter'></i>
                </a>

                <a href="settings.php?view=notifications" class="settings-nav-btn">
                    <span>Notification Preferences</span>
                    <i class='bx bx-bell'></i>
                </a>

            <?php elseif ($view === 'profile'): ?>
                
                <a href="settings.php" class="btn-back"><i class='bx bx-arrow-back'></i> Back to Settings</a>
                <h2>Edit Profile</h2>

                <form action="settings.php?view=profile" method="POST" class="profile-form">
                    
                    <h3 style="margin-bottom: 20px; color: #3C5B6F;">Choose your Avatar</h3>

                    <div class="avatar-selector">
                        <label class="avatar-option">
                            <input type="radio" name="avatar_option" value="default.png" <?= $current_pic == 'default.png' ? 'checked' : '' ?>>
                            <img src="images/default.png" alt="Default">
                            <span>Default</span>
                        </label>
                        <label class="avatar-option">
                            <input type="radio" name="avatar_option" value="male.png" <?= $current_pic == 'male.png' ? 'checked' : '' ?>>
                            <img src="images/male.png" alt="Male">
                            <span>Male</span>
                        </label>
                        <label class="avatar-option">
                            <input type="radio" name="avatar_option" value="female.png" <?= $current_pic == 'female.png' ? 'checked' : '' ?>>
                            <img src="images/female.png" alt="Female">
                            <span>Female</span>
                        </label>
                    </div>

                    <div class="input-group" style="margin-top: 30px;">
                        <label>Username (Name)</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($current_name) ?>" class="form-control">
                    </div>

                    <div class="input-group">
                        <label>Email (Cannot be changed here)</label>
                        <input type="text" value="<?= htmlspecialchars($email) ?>" class="form-control" disabled style="background: #e9ecef; cursor: not-allowed; opacity: 0.7;">
                    </div>

                    <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
                </form>

            <?php elseif ($view === 'notifications'): ?>
                
                <a href="settings.php" class="btn-back"><i class='bx bx-arrow-back'></i> Back to Settings</a>
                <h2>Notification Preferences</h2>
                <p style="margin-bottom: 30px;">Manage your system alert settings.</p>

                <form method="POST" action="settings.php?view=notifications">
                    
                    <div class="settings-nav-btn" style="cursor: default;">
                        <div>
                            <span style="display:block; font-weight:600;">System Notifications</span>
                            <span style="font-size: 13px; font-weight:normal; color:#666;">
                                <?php if ($is_notif_on): ?>
                                    <span style="color: #28a745; font-weight: 600;">Currently ON.</span> Toggle to turn off.
                                <?php else: ?>
                                    <span style="color: #dc3545; font-weight: 600;">Currently OFF.</span> Toggle to turn on.
                                <?php endif; ?>
                            </span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="notify_all" <?= $is_notif_on ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <button type="submit" name="update_notifications" class="btn-save" style="margin-top: 20px;">Save Preferences</button>
                </form>

            <?php elseif ($view === 'security'): ?>
                <a href="settings.php" class="btn-back"><i class='bx bx-arrow-back'></i> Back to Settings</a>
                <h2>Account Security</h2>
                
                <div class="profile-form" style="margin-bottom: 30px; text-align: left;">
                    <h3>Change Password</h3>
                    <form method="POST" action="settings.php?view=security">
                        <div class="input-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required class="form-control">
                        </div>
                        <div class="input-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" required class="form-control">
                        </div>
                        <div class="input-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required class="form-control">
                        </div>
                        <button type="submit" name="update_password" class="btn-save">Update Password</button>
                    </form>
                </div>

                <div class="profile-form" style="text-align: left;">
                    <h3>Change Email Address</h3>
                    <form method="POST" action="settings.php?view=security">
                        <div class="input-group">
                            <label>New Email Address</label>
                            <input type="email" name="new_email" required class="form-control">
                        </div>
                        <div class="input-group">
                            <label>Confirm with Password</label>
                            <input type="password" name="password_check" required class="form-control">
                        </div>
                        <button type="submit" name="update_email" class="btn-save">Update Email</button>
                    </form>
                </div>

            <?php endif; ?>

        </div>
    </section>
</body>
</html>