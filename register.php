<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($conn->real_escape_string($_POST['username']));
    $full_name = trim($conn->real_escape_string($_POST['full_name']));
    $password = $_POST['password'];

    if (empty($username) || empty($full_name) || empty($password)) {
        $_SESSION['error'] = "Semua bidang formulir wajib diisi!";
        $_SESSION['auth_mode'] = "register";
        header("Location: index.php");
        exit();
    }

    // Check if username already exists
    $checkSql = "SELECT id FROM users WHERE username = '$username'";
    $checkResult = $conn->query($checkSql);
    if ($checkResult->num_rows > 0) {
        $_SESSION['error'] = "Username sudah digunakan!";
        $_SESSION['auth_mode'] = "register";
        header("Location: index.php");
        exit();
    }

    // Hash password and insert
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $insertSql = "INSERT INTO users (username, password, full_name) VALUES ('$username', '$hashed_password', '$full_name')";
    
    if ($conn->query($insertSql)) {
        // Automatically log in the user
        $newUserId = $conn->insert_id;
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['username'] = $username;
        $_SESSION['full_name'] = $full_name;
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Gagal melakukan registrasi, silakan coba lagi.";
        $_SESSION['auth_mode'] = "register";
        header("Location: index.php");
        exit();
    }
}
?>
