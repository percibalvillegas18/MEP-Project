<?php
/**
 * User Management / My Profile
 * -------------------------------------------------------
 * Admin   → full user list with activate / deactivate / one-time reset link.
 * Others  → self-service profile: change own password, change own email.
 *           Cannot change own role.
 * -------------------------------------------------------
 */
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_login();                       // every role can reach this page

$isAdmin   = has_role('admin');
$allRoles  = ['admin', 'user', 'project_engineer', 'mep_engineer', 'project_manager'];
$roleLabels = [
    'admin'              => 'Admin',
    'user'               => 'User',
    'project_engineer'   => 'Project Engineer',
    'mep_engineer'       => 'MEP Engineer',
    'project_manager'    => 'Project Manager',
];

/* ---------- helpers --------------------------------------------------- */

/** Send a short-lived, single-use password reset link via configured SMTP. */
function send_password_reset_link(string $toEmail, string $toName, string $resetUrl): bool {
    require_once dirname(__DIR__, 2) . '/includes/mailer.php';
    $subject = 'MEP Projects Portal - Password Reset Link';
    $body    = "Hello {$toName},\r\n\r\n"
             . "An administrator created a password reset request for your account.\r\n\r\n"
             . "Open this single-use link within " . PASSWORD_RESET_MINUTES . " minutes:\r\n{$resetUrl}\r\n\r\n"
             . "If you did not expect this request, contact your administrator.\r\n\r\n"
             . "- MEP Projects Portal";
    return smtp_send($toEmail, $subject, $body);
}

// =====================================================================
//  POST handler
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // ---- Admin: Add user ----
    if ($isAdmin && $action === 'add') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = in_array($_POST['role'] ?? 'user', $allRoles, true) ? $_POST['role'] : 'user';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
            flash('error', 'Valid name, email, and a 12+ character password are required.');
            redirect('users.php');
        }
        try {
            $pdo->prepare("INSERT INTO users(name,email,password,role,status) VALUES(?,?,?,?,'active')")
                ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            audit_event($pdo,'create','users',(int)$pdo->lastInsertId(),null,'User created with role '.$role);
            flash('success', 'User created.');
        } catch (PDOException $e) {
            flash('error', 'Email already exists.');
        }
        redirect('users.php');
    }

    // ---- Admin: Toggle status (activate / deactivate) ----
    if ($isAdmin && $action === 'status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['user_id']) {
            flash('error', 'You cannot deactivate your own account.');
            redirect('users.php');
        }
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $pdo->prepare("UPDATE users SET status=?, auth_version=auth_version+1, updated_by=?, updated_at=NOW() WHERE id=?")
            ->execute([$status, $_SESSION['user_id'], $id]);
        audit_event($pdo,'status','users',$id,null,'User status changed to '.$status);
        flash('success', $status === 'active' ? 'User activated.' : 'User deactivated.');
        redirect('users.php');
    }

    // ---- Admin: Change role ----
    if ($isAdmin && $action === 'role') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['user_id']) {
            flash('error', 'You cannot change your own role.');
            redirect('users.php');
        }
        $role = in_array($_POST['role'] ?? 'user', $allRoles, true) ? $_POST['role'] : 'user';
        $pdo->prepare("UPDATE users SET role=?, auth_version=auth_version+1, updated_by=?, updated_at=NOW() WHERE id=?")
            ->execute([$role, $_SESSION['user_id'], $id]);
        audit_event($pdo,'role','users',$id,null,'User role changed to '.$role);
        flash('success', 'User role updated.');
        redirect('users.php');
    }

    // ---- Admin: issue a single-use password reset link ----
    if ($isAdmin && $action === 'reset_password') {
        $id = (int)($_POST['id'] ?? 0);
        $target = $pdo->prepare("SELECT name, email FROM users WHERE id=? AND status='active'");
        $target->execute([$id]);
        $target = $target->fetch();

        if (!$target) {
            flash('error', 'Active user not found.');
            redirect('users.php');
        }

        if(APP_BASE_URL===''||SMTP_HOST===''||SMTP_USER===''||SMTP_PASS===''){
            flash('error','Password reset email is not configured. Set MEP_APP_BASE_URL and MEP_SMTP_* environment variables.');
            redirect('users.php');
        }
        $plainToken=bin2hex(random_bytes(32));
        $tokenHash=hash('sha256',$plainToken,true);
        $expiresAt=date('Y-m-d H:i:s',time()+PASSWORD_RESET_MINUTES*60);
        $pdo->beginTransaction();
        try{
            $pdo->prepare("UPDATE password_reset_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL")->execute([$id]);
            $pdo->prepare("INSERT INTO password_reset_tokens(user_id,token_hash,expires_at,requested_by,requester_ip) VALUES(?,?,?,?,?)")
                ->execute([$id,$tokenHash,$expiresAt,$_SESSION['user_id'],$_SERVER['REMOTE_ADDR']??'']);
            $tokenId=(int)$pdo->lastInsertId();
            $resetUrl=APP_BASE_URL.'/reset_password.php?token='.rawurlencode($plainToken);
            if(!send_password_reset_link($target['email'],$target['name'],$resetUrl)) throw new RuntimeException('SMTP delivery failed.');
            audit_event($pdo,'password_reset_requested','users',$id,null,'Single-use password reset link issued');
            $pdo->commit();
            flash('success','A single-use reset link was sent to '.$target['email'].'.');
        }catch(Throwable $e){
            if($pdo->inTransaction())$pdo->rollBack();
            error_log('Password reset request failed: '.$e->getMessage());
            flash('error','Reset link could not be sent. Check the SMTP environment settings and server log.');
        }
        redirect('users.php');
    }

    // ---- Self-service: Change own password ----
    if ($action === 'self_password') {
        $current = $_POST['current_password'] ?? '';
        $newPw   = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // Verify current password
        $row = $pdo->prepare("SELECT password FROM users WHERE id=?");
        $row->execute([$_SESSION['user_id']]);
        $row = $row->fetch();

        if (!$row || !password_verify($current, $row['password'])) {
            flash('error', 'Current password is incorrect.');
            redirect('users.php');
        }
        if (strlen($newPw) < 12) {
            flash('error', 'New password must be at least 12 characters.');
            redirect('users.php');
        }
        if ($newPw !== $confirm) {
            flash('error', 'New passwords do not match.');
            redirect('users.php');
        }

        $pdo->prepare("UPDATE users SET password=?, auth_version=auth_version+1, updated_by=?, updated_at=NOW() WHERE id=?")
            ->execute([password_hash($newPw, PASSWORD_DEFAULT), $_SESSION['user_id'], $_SESSION['user_id']]);
        $_SESSION['auth_version'] = (int)($_SESSION['auth_version'] ?? 1) + 1;
        audit_event($pdo,'password_change','users',(int)$_SESSION['user_id'],null,'User changed own password');
        flash('success', 'Your password has been updated.');
        redirect('users.php');
    }

    // ---- Self-service: Change own email ----
    if ($action === 'self_email') {
        $newEmail = trim($_POST['new_email'] ?? '');
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please enter a valid email address.');
            redirect('users.php');
        }
        try {
            $pdo->prepare("UPDATE users SET email=?, updated_by=?, updated_at=NOW() WHERE id=?")
                ->execute([$newEmail, $_SESSION['user_id'], $_SESSION['user_id']]);
            $_SESSION['email'] = $newEmail;
            audit_event($pdo,'email_change','users',(int)$_SESSION['user_id'],null,'User changed own email');
            flash('success', 'Your email address has been updated.');
        } catch (PDOException $e) {
            flash('error', 'That email address is already in use.');
        }
        redirect('users.php');
    }

    redirect('users.php');
}

// =====================================================================
//  Data loading
// =====================================================================
if ($isAdmin) {
    $rows = $pdo->query("SELECT id,name,email,COALESCE(NULLIF(role,''),'user') AS role,status,created_at FROM users ORDER BY id DESC")->fetchAll();
}

// Current user info (for self-service section)
$me = $pdo->prepare("SELECT id,name,email,COALESCE(NULLIF(role,''),'user') AS role FROM users WHERE id=?");
$me->execute([$_SESSION['user_id']]);
$me = $me->fetch();

$pageTitle = $isAdmin ? 'User Management' : 'My Profile';
