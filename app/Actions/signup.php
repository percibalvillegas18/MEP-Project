<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';
if (!empty($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
if (!ALLOW_SELF_SIGNUP) { http_response_code(403); exit('Self-registration is disabled. Contact your administrator for access.'); }

// Self-signup is only available when an admin already exists
$adminExists = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn() > 0;
if (!$adminExists) {
    header('Location: setup_admin.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid name and email address.';
    } elseif (strlen($password) < 12) {
        $error = 'Password must be at least 12 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Self-signup always creates a 'user' role account
            $stmt = $pdo->prepare("INSERT INTO users(name,email,password,role,status) VALUES(?,?,?,?,'active')");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), 'user']);
            header('Location: login.php?registered=1'); exit;
        } catch (PDOException $e) {
            $error = 'An account with that email already exists.';
        }
    }
}
