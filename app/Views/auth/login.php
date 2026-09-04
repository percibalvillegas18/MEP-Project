<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — MEP Projects Portal</title>
<link rel="icon" href="favicon.ico">
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
<div class="auth-card">
    <div class="login-logo">
        <img src="assets/img/combined-logo.png" alt="Aala Tech × MEP Projects Portal" class="login-logo-img">
    </div>
    <h1>Welcome back</h1>
    <p class="subtitle">Sign in to manage and track your MEP projects in one place.</p>
    <p class="auth-version"><?= htmlspecialchars(app_version_label(), ENT_QUOTES, 'UTF-8') ?></p>

    <?php if (isset($_GET['setup'])): ?>
        <div class="alert success">Administrator created. You can log in now.</div>
    <?php endif; ?>
    <?php if (isset($_GET['registered'])): ?>
        <div class="alert success">Account created successfully. You can log in now.</div>
    <?php endif; ?>
    <?php if (isset($_GET['revoked'])): ?>
        <div class="alert error">Your session is no longer valid. Please sign in again.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['login_csrf'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="auth-input-wrap">
            <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            <input type="email" name="email" placeholder="Email" required autocomplete="email">
        </div>
        <div class="auth-input-wrap">
            <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input type="password" name="password" id="loginPass" placeholder="Password" required autocomplete="current-password">
            <button type="button" class="pass-toggle" onclick="togglePass('loginPass',this)" aria-label="Show password">
                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/></svg>
            </button>
        </div>
        <div class="auth-options">
            <span class="auth-check">Session closes after 2 hours of inactivity</span>
            <span class="auth-forgot" title="Contact your administrator">Forgot password? Contact Admin</span>
        </div>
        <button class="btn-signin">Sign In <span class="btn-arrow">&rarr;</span></button>
    </form>
    <div class="auth-divider"><span>or</span></div>

    <?php if (!$adminExists): ?>
        <p class="footer-text">First installation? <a href="setup_admin.php">Create Admin</a></p>
    <?php elseif (ALLOW_SELF_SIGNUP): ?>
        <p class="footer-text">Don't have an account? <a href="signup.php">Sign Up</a></p>
    <?php else: ?>
        <p class="footer-text">Need access? Contact your administrator.</p>
    <?php endif; ?>
</div>

<script>
function togglePass(id, btn) {
    var inp = document.getElementById(id);
    var open = btn.querySelector('.eye-open');
    var closed = btn.querySelector('.eye-closed');
    if (inp.type === 'password') {
        inp.type = 'text';
        open.style.display = 'none';
        closed.style.display = 'block';
    } else {
        inp.type = 'password';
        open.style.display = 'block';
        closed.style.display = 'none';
    }
}
</script>
</body>
</html>
