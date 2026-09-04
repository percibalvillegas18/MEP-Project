<?php
/**
 * AJAX endpoint: returns submittals matching a given boq_ref_no for a project.
 * Used by workplan.php MAS Ref. No. browse modal.
 * Filters submittals where boq_ref_no matches the provided boq_no value.
 * Returns JSON array of {id, submittal_reference, material_description, boq_ref_no, approved_date, status}.
 *
 * GET params:
 *   project_id  (int, required)
 *   boq_no      (string, required — the BOQ No. from project_progress)
 *   q           (string, optional search term)
 */
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$projectId = (int)($_GET['project_id'] ?? 0);
$boqNo = trim($_GET['boq_no'] ?? '');

if (!$projectId || $boqNo === '') {
    echo json_encode([]);
    exit;
}
require_project_access($pdo,$projectId);

$q = trim($_GET['q'] ?? '');

$sql = "SELECT id, submittal_reference, material_description, boq_ref_no, approved_date, status
        FROM submittals
        WHERE project_id = ? AND boq_ref_no = ?";
$params = [$projectId, $boqNo];

if ($q !== '') {
    $sql .= " AND (submittal_reference LIKE ? OR material_description LIKE ?)";
    $like = "%$q%";
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY submittal_reference ASC";
$st = $pdo->prepare($sql);
$st->execute($params);
echo json_encode($st->fetchAll(PDO::FETCH_ASSOC));
