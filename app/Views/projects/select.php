<?php include __DIR__.'/../../../includes/header.php'; ?>
<section class="panel">
    <div class="panel-head">
        <div><h2>Select a Project</h2><p class="muted">Choose a project to open its MEP Progress Tracker.</p></div>
        <form class="search" method="get"><input name="q" placeholder="Search projects..." value="<?= e($q) ?>"><button class="btn">Search</button></form>
    </div>
    <div class="select-project-grid">
    <?php foreach ($rows as $r):
        $pct = round((float)$r['overall_progress'],2);
        $items = (int)$r['progress_items'];
        $statusClass = strtolower(str_replace(' ','-',$r['status']));
    ?>
        <a href="project_progress.php?project_id=<?= $r['id'] ?>" class="project-card-link">
            <div class="project-select-card">
                <div class="psc-header">
                    <h3><?= e($r['project_name']) ?></h3>
                    <span class="badge badge-<?= e($statusClass) ?>"><?= e($r['status']) ?></span>
                </div>
                <p class="psc-location"><?= e($r['location']) ?></p>
                <div class="psc-progress">
                    <div class="progress-bar"><i style="width:<?= $pct ?>%"></i></div>
                    <span class="psc-pct"><?= $pct ?>%</span>
                </div>
                <small class="psc-meta"><?= $items ?> BOQ item<?= $items !== 1 ? 's' : '' ?></small>
            </div>
        </a>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
        <p class="empty">No projects found.<?= $q !== '' ? ' Try a different search.' : '' ?></p>
    <?php endif; ?>
    </div>
</section>

<style>
.select-project-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;padding:4px 0}
.project-card-link{text-decoration:none;color:inherit;display:block;border-radius:12px;transition:box-shadow .2s,transform .15s}
.project-card-link:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(25,43,72,.1)}
.project-select-card{background:#fff;border:1px solid #e2e7ef;border-radius:12px;padding:20px;display:flex;flex-direction:column;gap:10px;height:100%}
.psc-header{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
.psc-header h3{margin:0;font-size:16px;color:#172033;line-height:1.3}
.psc-location{margin:0;font-size:13px;color:#64748b}
.psc-progress{display:flex;align-items:center;gap:10px}
.psc-progress .progress-bar{flex:1}
.psc-pct{font-weight:700;font-size:15px;color:#172033;min-width:40px;text-align:right}
.psc-meta{color:#94a3b8;font-size:12px}
.badge-planning{background:#DBEAFE;color:#1E40AF}
.badge-active{background:#D1FAE5;color:#065F46}
.badge-on-hold{background:#FEF3C7;color:#92400E}
.badge-completed{background:#E0E7FF;color:#3730A3}
@media(max-width:500px){.select-project-grid{grid-template-columns:1fr}}
</style>
<?php include __DIR__.'/../../../includes/footer.php'; ?>
