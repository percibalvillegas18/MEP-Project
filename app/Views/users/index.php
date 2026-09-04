<?php include __DIR__.'/../../../includes/header.php'; ?>
<?php if ($isAdmin): ?>
<!-- ============================================================= -->
<!--  ADMIN VIEW: Add User + Full Account List                     -->
<!-- ============================================================= -->
<div class="two-col users-layout">
    <section class="panel">
        <h2>Add User</h2>
        <form method="post" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="add">
            <label>Name<input name="name" required></label>
            <label>Email<input type="email" name="email" required></label>
            <label>Temporary Password<input type="password" name="password" minlength="12" required></label>
            <label>Role
                <select name="role">
                    <option value="user">User</option>
                    <option value="project_engineer">Project Engineer</option>
                    <option value="mep_engineer">MEP Engineer</option>
                    <option value="project_manager">Project Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </label>
            <button class="btn">Create User</button>
        </form>
    </section>

    <section class="panel">
        <h2>Accounts</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td data-label="Name"><?= e($r['name']) ?></td>
                        <td data-label="Email"><?= e($r['email']) ?></td>
                        <td data-label="Role"><span class="role-badge role-<?= e($r['role']) ?>"><?= e($roleLabels[$r['role']] ?? $r['role']) ?></span></td>
                        <td data-label="Status"><span class="badge <?= $r['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>"><?= e($r['status']) ?></span></td>
                        <td data-label="Actions">
                            <div class="user-actions">
                                <?php if ((int)$r['id'] !== (int)$_SESSION['user_id']): ?>
                                <!-- Change Role -->
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="role">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <select name="role" onchange="this.form.submit()">
                                        <?php foreach ($roleLabels as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= $r['role'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                                <!-- Activate / Deactivate -->
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="status">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $r['status'] === 'active' ? 'inactive' : 'active' ?>">
                                    <button class="mini-btn <?= $r['status'] === 'active' ? 'mini-btn-danger' : 'mini-btn-success' ?>"><?= $r['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                                </form>
                                <!-- Reset Password (auto-generated, emailed) -->
                                <form method="post" onsubmit="return confirm('Send this user a single-use password reset link?')">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <button class="mini-btn mini-btn-reset">Reset Password</button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted">— You</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php endif; ?>

<!-- ============================================================= -->
<!--  SELF-SERVICE: Change own Password / Email (all roles)        -->
<!-- ============================================================= -->
<div class="<?= $isAdmin ? 'profile-section-admin' : 'profile-section-full' ?>">
    <div class="two-col profile-layout">
        <section class="panel">
            <h2>Change Password</h2>
            <form method="post" class="form-grid" id="selfPwForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="self_password">
                <label>Current Password<input type="password" name="current_password" required></label>
                <label>New Password<input type="password" name="new_password" id="newPw" minlength="12" required></label>
                <label>Confirm New Password<input type="password" name="confirm_password" id="confirmPw" minlength="12" required></label>
                <button class="btn">Update Password</button>
            </form>
        </section>

        <section class="panel">
            <h2>Change Email Address</h2>
            <p class="text-muted" style="margin:0 0 12px">Current email: <strong><?= e($me['email']) ?></strong></p>
            <form method="post" class="form-grid">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="self_email">
                <label>New Email Address<input type="email" name="new_email" required placeholder="Enter new email"></label>
                <button class="btn">Update Email</button>
            </form>
            <div class="profile-info">
                <p class="text-muted" style="margin:12px 0 0">Name: <strong><?= e($me['name']) ?></strong></p>
                <p class="text-muted" style="margin:4px 0 0">Role: <span class="role-badge role-<?= e($me['role']) ?>"><?= e($roleLabels[$me['role']] ?? $me['role']) ?></span></p>
            </div>
        </section>
    </div>
</div>

<script>
document.getElementById('selfPwForm')?.addEventListener('submit', function(e) {
    var np = document.getElementById('newPw').value;
    var cp = document.getElementById('confirmPw').value;
    if (np !== cp) { e.preventDefault(); alert('Passwords do not match.'); }
});
</script>

<?php include __DIR__.'/../../../includes/footer.php'; ?>
