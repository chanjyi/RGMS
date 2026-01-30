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

    // ✅ Admin auto-approved, others pending
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

    // ✅ explicitly fetch status too
    $stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $roleLower = strtolower($user['role'] ?? '');
            $status = strtoupper($user['status'] ?? 'PENDING');

            // ✅ Only non-admin must be approved
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
            $_SESSION['login_error'] = "Incorrect password!";
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
