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
        $_SESSION['message'] = "Email already exists!";
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

    if ($email === '' || $password === '') {
        $_SESSION['message'] = "Please fill in email and password.";
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
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['profile_pic'] = $row['profile_pic'];


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
                // Fallback
                header("Location: index.php");
            }
            $stmt->close();
            exit();
        } else {
            $_SESSION['message'] = "Incorrect password!";
        }
    } else {
        $_SESSION['message'] = "Email not found!";
    }

    $stmt->close();
    header("Location: index.php");
    exit();
}
?>
