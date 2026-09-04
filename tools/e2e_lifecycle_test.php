<?php
declare(strict_types=1);

$db = require __DIR__ . '/../config.php';
echo "=== Starting MEP Projects Version 007.4 E2E lifecycle test ===\n\n";

try {
    $db->beginTransaction();

    echo "[1] Creating a temporary project and progress item...\n";
    $project = $db->prepare("INSERT INTO projects(project_name) VALUES(?)");
    $project->execute(['E2E Test Tower - Phase 1']);
    $projectId = (int)$db->lastInsertId();
    $progress = $db->prepare(
        "INSERT INTO project_progress(project_id,discipline,boq_no,task,item_type,activity_weight,material_quantity)
         VALUES(?,?,?,?,?,?,?)"
    );
    $progress->execute([$projectId, 'Electrical', 'EL-PANEL-01', 'Electrical panel', 'Measurable Item', 25, 10]);

    echo "[2] Creating and advancing a submittal...\n";
    $submittal = $db->prepare(
        "INSERT INTO submittals(project_id,discipline,boq_ref_no,submittal_reference,status)
         VALUES(?,?,?,?,?)"
    );
    $submittal->execute([$projectId, 'Electrical', 'EL-PANEL-01', 'MAS-EL-001', 'P']);
    $submittalId = (int)$db->lastInsertId();
    $history = $db->prepare(
        "INSERT INTO workflow_status_history(entity_type,entity_id,project_id,status_type,old_status,new_status)
         VALUES(?,?,?,?,?,?)"
    );
    $history->execute(['submittal', $submittalId, $projectId, 'Submittal', null, 'P']);
    $db->prepare("UPDATE submittals SET status='B' WHERE id=?")->execute([$submittalId]);
    $history->execute(['submittal', $submittalId, $projectId, 'Submittal', 'P', 'B']);

    echo "[3] Reading lifecycle history...\n";
    $check = $db->prepare(
        "SELECT COUNT(*) FROM workflow_status_history WHERE project_id=? AND entity_id=?"
    );
    $check->execute([$projectId, $submittalId]);
    if ((int)$check->fetchColumn() !== 2) {
        throw new RuntimeException('Expected two submittal history records.');
    }

    $db->rollBack();
    echo "=== E2E lifecycle test passed; test data was rolled back. ===\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, "E2E lifecycle test failed: {$e->getMessage()}\n");
    exit(1);
}
