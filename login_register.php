<?php
session_start();
require 'config.php';

// ---------- REGISTER ----------
if (isset($_POST['register'])) {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    // Normalise HOD value if needed
    if ($role === 'HOD') $role = 'hod';

    if ($name === '' || $email === '' || $password === '' || $role === '') {
        $_SESSION['message'] = "Please fill in all fields.";
        header("Location: index.php");
        exit();
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Check if email exists
    $checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        $_SESSION['register_error'] = "Email already exists!";
        $_SESSION['active_form'] = 'register';
        $checkEmail->close();
        header("Location: index.php");
        exit();
    }
    $checkEmail->close();

    // Admin auto-approved, others pending
    $status = (strtolower($role) === 'admin') ? 'APPROVED' : 'PENDING';

    // Insert new user
    $insert = $conn->prepare(
        "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)"
    );
    $insert->bind_param("sssss", $name, $email, $passwordHash, $role, $status);
    $insert->execute();
    $insert->close();

    if ($status === 'APPROVED') {
        $_SESSION['message'] = "Admin registration successful! Please login.";
    } else {
        $_SESSION['message'] = "Registration successful! Please wait for admin approval.";
    }

    header("Location: index.php");
    exit();
}


// ---------- LOGIN ----------
if (isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $_SESSION['login_error'] = "Please fill in email and password.";
        $_SESSION['active_form'] = 'login';
        header("Location: index.php");
        exit();
    }

    // Fetch user including lockout fields
    $stmt = $conn->prepare("SELECT id, name, email, password, role, status, failed_login_attempts, account_locked_until FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $userId = $user['id'];

        // Check if account is locked
        if ($user['account_locked_until'] !== null) {
            $lockTime = strtotime($user['account_locked_until']);
            $currentTime = time();

            if ($currentTime < $lockTime) {
                $minutesLeft = ceil(($lockTime - $currentTime) / 60);
                $_SESSION['login_error'] = "Account temporarily locked due to multiple failed login attempts. Try again in {$minutesLeft} minutes or contact admin.";
                $_SESSION['active_form'] = 'login';
                $stmt->close();
                header("Location: index.php");
                exit();
            } else {
                // Lock period expired, reset the account
                $resetStmt = $conn->prepare("UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE id = ?");
                $resetStmt->bind_param("i", $userId);
                $resetStmt->execute();
                $resetStmt->close();
                $user['failed_login_attempts'] = 0;
            }
        }

        if (password_verify($password, $user['password'])) {
            // Reset failed attempts on successful login
            $resetStmt = $conn->prepare("UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE id = ?");
            $resetStmt->bind_param("i", $userId);
            $resetStmt->execute();
            $resetStmt->close();

            $roleLower = strtolower($user['role'] ?? '');
            $status = strtoupper($user['status'] ?? 'PENDING');

            // Only non-admin must be approved
            if ($roleLower !== 'admin' && $status !== 'APPROVED') {
                $_SESSION['login_error'] = "Your account is not approved yet. Please wait for admin approval.";
                $_SESSION['active_form'] = 'login';
                $stmt->close();
                header("Location: index.php");
                exit();
            }

            // SUCCESS: Login User
            $_SESSION['name']  = $user['name'];
            $_SESSION['role']  = $user['role'];
            $_SESSION['email'] = $user['email'];

            // Remember Me Logic
            if (isset($_POST['remember_me'])) {
                setcookie('user_email', $email, time() + (86400 * 30), "/");
            } else {
                if (isset($_COOKIE['user_email'])) {
                    setcookie('user_email', '', time() - 3600, "/");
                }
            }

            // Redirect based on role
            if ($roleLower === 'researcher') {
                header("Location: researcher_page.php");
            } elseif ($roleLower === 'reviewer') {
                header("Location: reviewer_page.php");
            } elseif ($roleLower === 'hod') {
                header("Location: hod_page.php");
            } elseif ($roleLower === 'admin') {
                header("Location: admin_page.php");
            } else {
                header("Location: index.php");
            }

            $stmt->close();
            exit();

        } else {
            // WRONG PASSWORD - Increment failed attempts
            $failedAttempts = $user['failed_login_attempts'] + 1;

            if ($failedAttempts >= 3) {
                // Lock account for 30 minutes
                $lockUntil = date('Y-m-d H:i:s', time() + (30 * 60));
                $updateStmt = $conn->prepare("UPDATE users SET failed_login_attempts = ?, account_locked_until = ? WHERE id = ?");
                $updateStmt->bind_param("isi", $failedAttempts, $lockUntil, $userId);
                $updateStmt->execute();
                $updateStmt->close();

                $_SESSION['login_error'] = "Account locked due to 3 failed login attempts. Please try again in 30 minutes or contact admin.";
            } else {
                $updateStmt = $conn->prepare("UPDATE users SET failed_login_attempts = ? WHERE id = ?");
                $updateStmt->bind_param("ii", $failedAttempts, $userId);
                $updateStmt->execute();
                $updateStmt->close();

                $attemptsLeft = 3 - $failedAttempts;
                $_SESSION['login_error'] = "Incorrect password! {$attemptsLeft} attempt(s) remaining.";
            }
            $_SESSION['active_form'] = 'login';
        }
    } else {
        $_SESSION['login_error'] = "Email not found!";
        $_SESSION['active_form'] = 'login';
    }

    $stmt->close();
    header("Location: index.php");
    exit();
}
?>
