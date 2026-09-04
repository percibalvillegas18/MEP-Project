<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_login();

$projectId = (int)($_GET['project_id'] ?? 0);
require_project_permission($pdo,$projectId,'report.export');
if (!$projectId) { flash('error','Select a project first.'); redirect('projects.php'); }

$st = $pdo->prepare("SELECT * FROM projects WHERE id=?"); $st->execute([$projectId]); $project = $st->fetch();
if (!$project) { flash('error','Project not found.'); redirect('projects.php'); }

$rows = $pdo->prepare("SELECT * FROM project_progress WHERE project_id=? ORDER BY discipline ASC, id ASC");
$rows->execute([$projectId]); $rows = $rows->fetchAll();

$disciplines = $pdo->prepare("SELECT discipline, CASE WHEN SUM(activity_weight)<=0 THEN 0.00 WHEN SUM(activity_weight*percentage_complete)>=SUM(activity_weight)*100 THEN 100.00 ELSE LEAST(99.99,ROUND(SUM(activity_weight*percentage_complete)/SUM(activity_weight),2)) END progress, COUNT(*) total FROM project_progress WHERE project_id=? AND item_type='Measurable Item' GROUP BY discipline ORDER BY discipline");
$disciplines->execute([$projectId]); $disciplineSummary = $disciplines->fetchAll();

$overallSt = $pdo->prepare("SELECT CASE WHEN COALESCE(SUM(activity_weight),0)<=0 THEN 0.00 WHEN SUM(activity_weight*percentage_complete)>=SUM(activity_weight)*100 THEN 100.00 ELSE LEAST(99.99,ROUND(SUM(activity_weight*percentage_complete)/SUM(activity_weight),2)) END FROM project_progress WHERE project_id=? AND item_type='Measurable Item'");
$overallSt->execute([$projectId]); $overall = (int)$overallSt->fetchColumn();

$totalItems = count($rows);
$completedItems = count(array_filter($rows, fn($r) => (int)$r['percentage_complete'] >= 100));
$inProgressItems = count(array_filter($rows, fn($r) => (int)$r['percentage_complete'] > 0 && (int)$r['percentage_complete'] < 100));
$notStartedItems = count(array_filter($rows, fn($r) => (int)$r['percentage_complete'] === 0));

function pct_bg(int $p): string {
    if ($p >= 100) return '#166534';
    if ($p >= 76) return '#22C55E';
    if ($p >= 51) return '#4ADE80';
    if ($p >= 26) return '#86EFAC';
    return '#BBF7D0';
}
function pct_fg(int $p): string { return $p >= 76 ? '#fff' : '#172033'; }
function priority_color(string $p): string {
    return match($p) { 'High' => '#EF4444', 'Medium' => '#F59E0B', 'Low' => '#3B82F6', 'Complete' => '#22C55E', default => '#64748b' };
}
