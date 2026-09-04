<?php
/**
 * AJAX endpoint: returns BOQ items for a given project.
 * Used by submittals.php BOQ Ref. No. search modal.
 * Returns JSON array of {id, boq_no, discipline, task, material_description}.
 */
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$projectId = (int)($_GET['project_id'] ?? 0);
if (!$projectId) {
    echo json_encode([]);
    exit;
}
require_project_access($pdo,$projectId);

$q = trim($_GET['q'] ?? '');

$sql = "SELECT pp.id,pp.boq_no,pp.discipline,pp.task,pp.material_description,
        (SELECT COUNT(*) FROM project_progress d WHERE d.project_id=pp.project_id AND d.boq_no=pp.boq_no AND d.item_type='Measurable Item') duplicate_count
        FROM project_progress pp WHERE pp.project_id=? AND pp.item_type='Measurable Item' AND pp.boq_no<>''";
$params = [$projectId];

if ($q !== '') {
    $sql .= " AND (pp.boq_no LIKE ? OR pp.task LIKE ? OR pp.material_description LIKE ? OR pp.discipline LIKE ?)";
    $like = "%$q%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$sql .= " ORDER BY pp.discipline ASC, pp.boq_no ASC, pp.id ASC";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
