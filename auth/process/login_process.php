<?php
session_start();
require_once "../config/db.php"; // make sure $pdo is defined in db.php

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        // redirect to their dashboard
        header("Location: ../{$user['role']}/dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid email or password";
        header("Location: login.php");
        exit();
    }
}
