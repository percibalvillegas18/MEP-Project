<?php
/**
 * AJAX endpoint: return submittals for a given project filtered by status A or B.
 * Used by procurement.php MAS Submittal Ref No. lookup.
 *
 * GET params:
 *   project_id  (int, required)
 *   q           (string, optional search term)
 *
 * Returns JSON array of matching submittals.
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

$sql = "SELECT id, submittal_reference, material_description, manufacturer, boq_ref_no, approved_date, status
        FROM submittals
        WHERE project_id = ? AND status IN ('A','B')";
$params = [$projectId];

if ($q !== '') {
    $sql .= " AND (submittal_reference LIKE ? OR material_description LIKE ? OR boq_ref_no LIKE ?)";
    $like = "%$q%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY submittal_reference ASC";
$st = $pdo->prepare($sql);
$st->execute($params);
echo json_encode($st->fetchAll(PDO::FETCH_ASSOC));
