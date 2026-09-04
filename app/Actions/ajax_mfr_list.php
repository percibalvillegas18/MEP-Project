<?php
/**
 * AJAX endpoint: returns distinct manufacturers from the submittals table.
 * Used by submittals.php Mfr List modal.
 * Returns JSON array of {manufacturer, country_origin}.
 * Only returns rows where manufacturer is not empty.
 * Duplicates are skipped — each manufacturer appears once (with its most recent country_origin).
 */
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');

$sql = "SELECT manufacturer,country_origin FROM (
            SELECT s.manufacturer,s.country_origin,
                   ROW_NUMBER() OVER (PARTITION BY s.manufacturer ORDER BY s.id DESC) AS row_rank
            FROM submittals s JOIN projects p ON p.id=s.project_id
            WHERE " . project_scope_clause('p.id') . " AND s.manufacturer IS NOT NULL AND s.manufacturer != ''";
$params = [];

if ($q !== '') {
    $sql .= " AND (s.manufacturer LIKE ? OR s.country_origin LIKE ?)";
    $like = "%$q%";
    $params = array_merge($params, [$like, $like]);
}

$sql .= ") ranked WHERE row_rank=1 ORDER BY manufacturer ASC";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
