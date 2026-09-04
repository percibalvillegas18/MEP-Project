<?php include __DIR__.'/../../../includes/header.php'; ?>
<?php if ($showForm): ?>
<section class="panel">
    <div class="panel-head"><div><h2><?= $edit ? 'Edit Project' : 'Add Project' ?></h2><p class="muted">Create the project master record used by the MEP Project Progress Tracker.</p></div></div>
    <form method="post" class="form-grid project-form-grid">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= e((string)($edit['id'] ?? '')) ?>">
        <label>Project Name :<input name="project_name" required value="<?= e($edit['project_name'] ?? '') ?>"></label>
        <label>Location :<input name="location" value="<?= e($edit['location'] ?? '') ?>"></label>
        <label>Client :<input name="client" value="<?= e($edit['client'] ?? '') ?>"></label>
        <label>General Contractor :<input name="general_contractor" value="<?= e($edit['general_contractor'] ?? '') ?>"></label>
        <label>Consultant :<input name="consultant" value="<?= e($edit['consultant'] ?? '') ?>"></label>
        <label>Project Manager :<input name="project_manager" value="<?= e($edit['project_manager'] ?? '') ?>"></label>
        <label>Start Date :<input type="date" name="start_date" value="<?= e($edit['start_date'] ?? '') ?>"></label>
        <label>End Date :<input type="date" name="end_date" value="<?= e($edit['end_date'] ?? '') ?>"></label>
        <label>Status :
            <select name="status">
                <?php foreach ($projectStatuses as $s): ?><option value="<?= e($s) ?>" <?= $s === ($edit['status'] ?? 'Active') ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
            </select>
        </label>
        <div class="form-actions project-actions">
            <button class="btn" type="submit"><?= $edit ? 'Update Project' : 'Add Project' ?></button>
            <a class="btn ghost" href="projects.php">Cancel</a>
        </div>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <div class="panel-head">
        <div><h2>Project List</h2><p class="muted">Manage your project master records.</p></div>
        <div class="panel-head-actions">
            <?php if (!$showForm && has_role('admin','project_manager')): ?><a href="?add=1" class="btn">+ New Project</a><?php endif; ?>
            <form class="search"><input name="q" placeholder="Search projects..." value="<?= e($q) ?>"><button class="btn">Search</button></form>
        </div>
    </div>
    <div class="table-wrap">
        <table class="projects-table">
            <thead><tr><th>Project Name</th><th>Location</th><th>Status</th><th>MEP Progress</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td data-label="Project Name"><strong><?= e($r['project_name']) ?></strong></td>
                    <td data-label="Location"><?= e($r['location']) ?></td>
                    <td data-label="Status"><span class="badge"><?= e($r['status']) ?></span></td>
                    <td data-label="MEP Progress"><div class="progress-mini"><span style="width:<?= (float)$r['overall_progress'] ?>%"></span></div><small><?= number_format((float)$r['overall_progress'],2) ?>% &middot; <?= (int)$r['progress_items'] ?> items</small></td>
                    <td class="actions" data-label="Actions">
                        <?php if (!empty($r['can_edit_project'])): ?><a href="?edit=<?= $r['id'] ?>">Edit</a><?php endif; ?>
                        <?php if (!empty($r['can_manage_assignments'])): ?><a href="mvc.php?route=project-members&amp;project_id=<?= $r['id'] ?>">Members &amp; Roles</a><?php endif; ?>
                        <?php if (has_role('admin')): ?>
                        <form method="post" onsubmit="return confirm('Delete this project and all linked records?')"><input type="hidden" name="csrf_token" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="link-danger">Delete</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="5" class="empty">No projects found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<style>
.panel-head-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
</style>
<?php include __DIR__.'/../../../includes/footer.php'; ?>
