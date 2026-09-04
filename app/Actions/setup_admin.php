<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

// Block if an admin already exists
$adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
if ($adminCount > 0) {
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
        $error = 'Enter a valid name/email and a password with at least 12 characters.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO users(name,email,password,role,status) VALUES(?,?,?,?,'active')");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), 'admin']);
            header('Location: login.php?setup=1'); exit;
        } catch (PDOException $e) {
            $error = 'That email is already registered.';
        }
    }
}
