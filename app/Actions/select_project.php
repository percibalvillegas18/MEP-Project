<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_login();

$q = trim($_GET['q'] ?? '');
$scope=project_scope_clause('p.id');

if ($q !== '') {
    $st = $pdo->prepare("SELECT p.*, COUNT(pp.id) progress_items, CASE WHEN COALESCE(SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END),0)<=0 THEN 0.00 WHEN SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight*pp.percentage_complete ELSE 0 END)>=SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END)*100 THEN 100.00 ELSE LEAST(99.99,ROUND(SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight*pp.percentage_complete ELSE 0 END)/SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END),2)) END overall_progress
        FROM projects p LEFT JOIN project_progress pp ON pp.project_id=p.id
        WHERE (p.project_name LIKE ? OR p.location LIKE ?) AND $scope
        GROUP BY p.id ORDER BY p.project_name ASC");
    $like = "%$q%";
    $st->execute([$like,$like]);
    $rows = $st->fetchAll();
} else {
    $rows = $pdo->query("SELECT p.*, COUNT(pp.id) progress_items, CASE WHEN COALESCE(SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END),0)<=0 THEN 0.00 WHEN SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight*pp.percentage_complete ELSE 0 END)>=SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END)*100 THEN 100.00 ELSE LEAST(99.99,ROUND(SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight*pp.percentage_complete ELSE 0 END)/SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END),2)) END overall_progress
        FROM projects p LEFT JOIN project_progress pp ON pp.project_id=p.id WHERE $scope GROUP BY p.id ORDER BY p.project_name ASC")->fetchAll();
}

$pageTitle = 'Select Project — MEP Progress Tracker';
