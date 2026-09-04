<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';

if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

function login_throttle_keys(string $email): array {
    $ip=(string)($_SERVER['REMOTE_ADDR']??'unknown');$email=strtolower(trim($email));
    return ['pair'=>hash('sha256','pair|'.$ip.'|'.$email),'email'=>hash('sha256','email|'.$email),'ip'=>hash('sha256','ip|'.$ip)];
}

$adminExists = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn() > 0;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!$token || !hash_equals((string)($_SESSION['login_csrf'] ?? ''), $token)) {
        http_response_code(419);
        exit('Invalid security token. Please refresh and try again.');
    }

    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $throttleKeys=login_throttle_keys($email);$attemptRows=[];
    $attempt=$pdo->prepare('SELECT client_key,attempts,locked_until FROM login_attempts WHERE client_key IN (?,?,?)');$attempt->execute(array_values($throttleKeys));
    foreach($attempt->fetchAll() as $row)$attemptRows[$row['client_key']]=$row;

    $locked=false;foreach($attemptRows as $row){if(!empty($row['locked_until'])&&strtotime((string)$row['locked_until'])>time()){$locked=true;break;}}
    if ($locked) {
        $error = 'Too many failed attempts. Please try again later.';
    } else {
        $userStatement = $pdo->prepare("SELECT id,name,email,password,role,status,auth_version FROM users WHERE email=? LIMIT 1");
        $userStatement->execute([$email]);
        $user = $userStatement->fetch();
        $valid = $user && $user['status'] === 'active' && password_verify($password, (string)$user['password']);

        if (!$valid) {
            $upsert = $pdo->prepare(
                "INSERT INTO login_attempts(client_key,attempts,locked_until,last_attempt)
                 VALUES(?,?,?,NOW())
                 ON DUPLICATE KEY UPDATE attempts=VALUES(attempts),locked_until=VALUES(locked_until),last_attempt=NOW()"
            );
            $thresholds=['pair'=>5,'email'=>10,'ip'=>25];$lockedUntil=null;
            foreach($throttleKeys as $type=>$key){$attempts=(int)($attemptRows[$key]['attempts']??0)+1;$until=$attempts>=$thresholds[$type]?gmdate('Y-m-d H:i:s',time()+900):null;$upsert->execute([$key,$attempts,$until]);if($until)$lockedUntil=$until;}
            $error = $lockedUntil ? 'Too many failed attempts. Please try again in 15 minutes.' : 'Invalid email or password.';
        } else {
            $pdo->prepare('DELETE FROM login_attempts WHERE client_key IN (?,?,?)')->execute(array_values($throttleKeys));
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['auth_version'] = (int)$user['auth_version'];
            $_SESSION['last_activity'] = time();
            $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
            audit_event($pdo, 'login', 'authentication', (int)$user['id'], null, 'User signed in');
            header('Location: dashboard.php');
            exit;
        }
    }
}
