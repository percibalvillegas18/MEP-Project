<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MEP Progress Report — <?= e($project['project_name']) ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#172033;background:#fff;font-size:11px;line-height:1.5}
.report{max-width:1100px;margin:0 auto;padding:24px 30px}

/* Header */
.report-header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #0D9488;padding-bottom:16px;margin-bottom:20px}
.report-header h1{font-size:20px;color:#0D9488;margin-bottom:2px}
.report-header .subtitle{font-size:12px;color:#64748b}
.report-header .meta{text-align:right;font-size:11px;color:#64748b}
.report-header .meta strong{color:#172033}

/* Summary cards */
.summary-row{display:flex;gap:12px;margin-bottom:20px}
.summary-card{flex:1;border:1px solid #e2e7ef;border-radius:8px;padding:12px 16px;text-align:center}
.summary-card .label{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin-bottom:4px}
.summary-card .value{font-size:22px;font-weight:800}
.summary-card.overall .value{color:#0D9488}

/* Progress bar */
.progress-bar-report{height:10px;background:#e5e7eb;border-radius:5px;overflow:hidden;margin-top:6px}
.progress-bar-report i{display:block;height:100%;background:#0D9488;border-radius:5px}

/* Project info */
.project-info{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px 20px;margin-bottom:20px;font-size:11px}
.project-info dt{color:#64748b;font-weight:600}
.project-info dd{margin:0 0 4px}

/* Discipline summary */
.disc-summary{margin-bottom:20px}
.disc-summary h2{font-size:14px;margin-bottom:8px;color:#172033;border-bottom:1px solid #e5e7eb;padding-bottom:4px}
.disc-summary-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px}
.disc-card{border:1px solid #e2e7ef;border-radius:6px;padding:10px 12px;display:flex;align-items:center;justify-content:space-between}
.disc-card strong{font-size:12px}
.disc-card .disc-pct{font-weight:800;font-size:14px;color:#0D9488}

/* Tables */
.discipline-section{margin-bottom:18px;page-break-inside:avoid}
.discipline-section h3{font-size:13px;background:#f1f5f9;padding:8px 12px;border-radius:6px 6px 0 0;border:1px solid #e2e7ef;border-bottom:2px solid #0D9488;display:flex;justify-content:space-between}
.report-table{width:100%;border-collapse:collapse;font-size:10px}
.report-table th{background:#f8fafc;font-weight:700;text-align:center;padding:7px 6px;border:1px solid #e2e7ef;white-space:nowrap}
.report-table td{padding:6px;border:1px solid #e2e7ef;vertical-align:middle}
.report-table .text-left{text-align:left}
.report-table .text-center{text-align:center}
.pct-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-weight:700;font-size:10px;min-width:38px;text-align:center}
.priority-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-weight:700;font-size:10px;color:#fff}

/* Toolbar (screen only) */
.toolbar{display:flex;gap:10px;margin-bottom:20px;align-items:center}
.toolbar .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border:none;border-radius:6px;font-weight:700;font-size:13px;cursor:pointer;text-decoration:none;transition:all .2s}
.btn-pdf{background:#0D9488;color:#fff}.btn-pdf:hover{background:#0B7C72}
.btn-back{background:#f1f5f9;color:#172033;border:1px solid #cbd5e1}.btn-back:hover{background:#e2e8f0}

/* Print styles */
@media print {
    .toolbar{display:none!important}
    body{font-size:10px}
    .report{padding:0;max-width:100%}
    .report-header{border-bottom-width:2px}
    .summary-card{border-width:1px}
    .discipline-section{page-break-inside:avoid}
    @page{size:landscape;margin:12mm 10mm}
}
</style>
</head>
<body>
<div class="report">

<div class="toolbar">
    <a href="project_progress.php?project_id=<?= $projectId ?>" class="btn btn-back">&larr; Back to Tracker</a>
    <button class="btn btn-pdf" onclick="window.print()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Download as PDF
    </button>
</div>

<div class="report-header">
    <div>
        <h1>MEP Project Progress Report</h1>
        <div class="subtitle"><?= e($project['project_name']) ?></div>
    </div>
    <div class="meta">
        <div>Report Date: <strong><?= date('F j, Y') ?></strong></div>
        <div>Location: <strong><?= e($project['location']) ?></strong></div>
        <div>Status: <strong><?= e($project['status']) ?></strong></div>
    </div>
</div>

<dl class="project-info">
    <dt>Client</dt><dd><?= e($project['client'] ?: '—') ?></dd>
    <dt>General Contractor</dt><dd><?= e($project['general_contractor'] ?: '—') ?></dd>
    <dt>Consultant</dt><dd><?= e($project['consultant'] ?: '—') ?></dd>
    <dt>Project Manager</dt><dd><?= e($project['project_manager'] ?: '—') ?></dd>
    <dt>Start Date</dt><dd><?= e($project['start_date'] ?: '—') ?></dd>
    <dt>End Date</dt><dd><?= e($project['end_date'] ?: '—') ?></dd>
</dl>

<div class="summary-row">
    <div class="summary-card overall">
        <div class="label">Overall Progress</div>
        <div class="value"><?= $overall ?>%</div>
        <div class="progress-bar-report"><i style="width:<?= $overall ?>%"></i></div>
    </div>
    <div class="summary-card">
        <div class="label">Total Items</div>
        <div class="value"><?= $totalItems ?></div>
    </div>
    <div class="summary-card">
        <div class="label">Completed</div>
        <div class="value" style="color:#22C55E"><?= $completedItems ?></div>
    </div>
    <div class="summary-card">
        <div class="label">In Progress</div>
        <div class="value" style="color:#F59E0B"><?= $inProgressItems ?></div>
    </div>
    <div class="summary-card">
        <div class="label">Not Started</div>
        <div class="value" style="color:#94a3b8"><?= $notStartedItems ?></div>
    </div>
</div>

<div class="disc-summary">
    <h2>Discipline Summary</h2>
    <div class="disc-summary-grid">
    <?php foreach ($disciplineSummary as $d): ?>
        <div class="disc-card">
            <strong><?= e($d['discipline']) ?></strong>
            <span class="disc-pct"><?= (int)$d['progress'] ?>% <small style="color:#64748b;font-weight:400">(<?= (int)$d['total'] ?> items)</small></span>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<?php
$current = '';
foreach ($rows as $r):
    if ($current !== $r['discipline']):
        if ($current !== '') echo '</tbody></table></div>';
        $current = $r['discipline'];
        $sum = array_values(array_filter($disciplineSummary, fn($d) => $d['discipline'] === $current));
        $dp = $sum ? (int)$sum[0]['progress'] : 0;
?>
<div class="discipline-section">
    <h3><span><?= e($current) ?></span><span>Progress: <?= $dp ?>%</span></h3>
    <table class="report-table">
        <thead><tr>
            <th>#</th><th>BOQ No.</th><th>Priority</th><th>Task</th><th>Task Description</th><th>Material Quantity</th><th>Unit</th><th>% Complete</th><th>Status</th><th>Notes</th><th>Remarks</th>
        </tr></thead>
        <tbody>
<?php endif;
    $pct = round((float)$r['percentage_complete'],2);
    static $rowNum = 0; $rowNum++;
?>
        <tr>
            <td class="text-center"><?= $rowNum ?></td>
            <td class="text-center"><?= e($r['boq_no']) ?></td>
            <td class="text-center"><span class="priority-badge" style="background:<?= priority_color($r['priority']) ?>"><?= e($r['priority']) ?></span></td>
            <td class="text-left"><strong><?= e($r['task']) ?></strong></td>
            <td class="text-left"><?= e($r['material_description']) ?></td>
            <td class="text-center"><?= $r['material_quantity'] !== null ? e(rtrim(rtrim(number_format((float)$r['material_quantity'],2,'.',''),'0'),'.')) : '—' ?></td>
            <td class="text-center"><?= e($r['unit']) ?></td>
            <td class="text-center"><span class="pct-badge" style="background:<?= pct_bg($pct) ?>;color:<?= pct_fg($pct) ?>"><?= $pct ?>%</span></td>
            <td class="text-center"><?= e($r['status']) ?></td>
            <td class="text-left"><?= e($r['notes'] ?? '') ?></td>
            <td class="text-left"><?= e($r['remarks'] ?? '') ?></td>
        </tr>
<?php endforeach; if ($current !== '') echo '</tbody></table></div>'; ?>

<?php if (!$rows): ?>
<p style="padding:20px;color:#64748b;text-align:center">No BOQ items recorded for this project.</p>
<?php endif; ?>

<div style="margin-top:24px;padding-top:12px;border-top:1px solid #e2e7ef;font-size:10px;color:#94a3b8;text-align:center">
    MEP Projects Portal &middot; Report generated on <?= date('F j, Y \a\t g:i A') ?>
</div>

</div>
</body>
</html>
