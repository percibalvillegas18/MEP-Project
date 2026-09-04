<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();

// Track last_logout timestamp before destroying session
if (!empty($_SESSION['user_id'])) {
    audit_event($pdo,'logout','authentication',(int)$_SESSION['user_id'],null,'User signed out');
    $pdo->prepare("UPDATE users SET last_logout=NOW() WHERE id=?")->execute([$_SESSION['user_id']]);
}

// Clear remember-me cookie on logout
setcookie('remember_me', '', ['expires' => time() - 3600, 'path' => '/']);
destroy_login_session();
header('Location: login.php'); exit;
