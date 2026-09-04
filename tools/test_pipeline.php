<?php
declare(strict_types=1);

$db = require __DIR__ . '/../config.php';
echo "--- Starting MEP Project Portal Version 007.4 pipeline test ---\n\n";

try {
    $db->beginTransaction();

    echo "[1] Creating a temporary project...\n";
    $project = $db->prepare("INSERT INTO projects(project_name) VALUES(?)");
    $project->execute(['Pipeline Test Project']);
    $projectId = (int)$db->lastInsertId();

    echo "[2] Creating a measurable progress item...\n";
    $progress = $db->prepare(
        "INSERT INTO project_progress(project_id,discipline,item_type,activity_weight,planned_percentage,boq_no,task)
         VALUES(?,?,?,?,?,?,?)"
    );
    $progress->execute([$projectId, 'HVAC', 'Measurable Item', 10, 25, 'TEST-001', 'Pipeline test activity']);

    echo "[3] Creating a submittal and procurement record...\n";
    $submittal = $db->prepare(
        "INSERT INTO submittals(project_id,discipline,boq_ref_no,submittal_reference,status)
         VALUES(?,?,?,?,?)"
    );
    $submittal->execute([$projectId, 'HVAC', 'TEST-001', 'MAS-TEST-001', 'A']);
    $submittalId = (int)$db->lastInsertId();
    $procurement = $db->prepare(
        "INSERT INTO procurement(project_id,submittal_reference_id,material_description,boq_ref_no,status)
         VALUES(?,?,?,?,?)"
    );
    $procurement->execute([$projectId, $submittalId, 'Pipeline test material', 'TEST-001', 'Not Started']);

    echo "[4] Verifying workflow history and relations...\n";
    $history = $db->prepare(
        "INSERT INTO workflow_status_history(entity_type,entity_id,project_id,status_type,new_status)
         VALUES(?,?,?,?,?)"
    );
    $history->execute(['submittal', $submittalId, $projectId, 'Submittal', 'A']);
    $check = $db->prepare("SELECT COUNT(*) FROM procurement WHERE project_id=? AND submittal_reference_id=?");
    $check->execute([$projectId, $submittalId]);
    if ((int)$check->fetchColumn() !== 1) {
        throw new RuntimeException('Procurement relation check failed.');
    }

    $db->rollBack();
    echo " -> All pipeline checks passed; test data was rolled back.\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, "Pipeline test failed: {$e->getMessage()}\n");
    exit(1);
}
