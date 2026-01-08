<?php
session_start();
require 'config.php';

// ---------- REGISTER ----------
if (isset($_POST['register'])) {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    // If your ENUM uses 'hod', normalise it here:
    if ($role === 'HOD') {
        $role = 'hod';
    }

    // Simple validation
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
        $_SESSION['register_error'] = "Email already exists!"; // Changed from 'message'
        $_SESSION['active_form'] = 'register'; // Switch to register tab
        $checkEmail->close();
        header("Location: index.php");
        exit();
    }
    $checkEmail->close();

    // Insert new user
    $insert = $conn->prepare(
        "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)"
    );
    $insert->bind_param("ssss", $name, $email, $passwordHash, $role);
    $insert->execute();
    $insert->close();

    $_SESSION['message'] = "Registration successful! Please login.";
    header("Location: index.php");
    exit();
}


// ---------- LOGIN ----------
if (isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Error 1: Empty Fields
    if ($email === '' || $password === '') {
        $_SESSION['login_error'] = "Please fill in email and password.";
        $_SESSION['active_form'] = 'login'; // Keep login form open
        header("Location: index.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // SUCCESS: Login User
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            
            // Remember Me Logic (from previous step)
            if (isset($_POST['remember_me'])) {
                setcookie('user_email', $email, time() + (86400 * 30), "/"); 
            } else {
                if (isset($_COOKIE['user_email'])) {
                    setcookie('user_email', '', time() - 3600, "/"); 
                }
            }

            // Redirect based on role
            if ($user['role'] === 'researcher') {
                header("Location: researcher_page.php");
            } elseif ($user['role'] === 'reviewer') {
                header("Location: reviewer_page.php");
            } elseif ($user['role'] === 'hod' || $user['role'] === 'HOD') {
                header("Location: hod_page.php");
            } elseif ($user['role'] === 'admin') {
                header("Location: admin_page.php");
            } else {
                header("Location: index.php");
            }
            $stmt->close();
            exit();

        } else {
            // Error 2: Wrong Password
            $_SESSION['login_error'] = "Incorrect password!";
            $_SESSION['active_form'] = 'login'; // Keep login form open
        }
    } else {
        // Error 3: Email Not Found
        $_SESSION['login_error'] = "Email not found!";
        $_SESSION['active_form'] = 'login'; // Keep login form open
    }

    $stmt->close();
    header("Location: index.php"); // Redirect back to show error
    exit();
}
?>
