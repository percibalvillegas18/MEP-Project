<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_login();

$projectStatuses = ['Planning','Active','On Hold','Completed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        require_admin();
        $deleteId=(int)($_POST['id'] ?? 0);
        $files=[];
        $f=$pdo->prepare("SELECT work_status_image_before file_name,work_status_image_before_checksum checksum FROM workplan WHERE project_id=? AND work_status_image_before<>'' UNION ALL SELECT wp.file_name,wp.checksum FROM workplan_photos wp JOIN workplan w ON w.id=wp.workplan_id WHERE w.project_id=?");
        $f->execute([$deleteId,$deleteId]);$files=$f->fetchAll();
        $pdo->beginTransaction();
        try { $pdo->prepare("DELETE FROM projects WHERE id=?")->execute([$deleteId]); audit_event($pdo,'delete','projects',$deleteId,null,'Project deleted'); $pdo->commit(); }
        catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
        foreach($files as $file)remove_or_queue_evidence($pdo,(string)$file['file_name'],$file['checksum']??null,'Project deleted');
        flash('success', 'Project deleted.');
        redirect('projects.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    if($id)require_project_permission($pdo,$id,'project.edit');
    else require_role('admin','project_manager');
    $projectName = trim($_POST['project_name'] ?? '');
    $status = $_POST['status'] ?? 'Active';
    if (!in_array($status, $projectStatuses, true)) $status = 'Active';

    if ($projectName === '') {
        flash('error', 'Project Name is required.');
        redirect('projects.php' . ($id ? '?edit=' . $id : '?add=1'));
    }

    $data = [
        $projectName,
        trim($_POST['location'] ?? ''),
        trim($_POST['client'] ?? ''),
        trim($_POST['general_contractor'] ?? ''),
        trim($_POST['consultant'] ?? ''),
        trim($_POST['project_manager'] ?? ''),
        ($_POST['start_date'] ?? '') ?: null,
        ($_POST['end_date'] ?? '') ?: null,
        $status
    ];
    if (!valid_date_value($data[6]) || !valid_date_value($data[7]) || !dates_in_order($data[6],$data[7])) {
        flash('error','Use valid YYYY-MM-DD dates; End Date cannot be earlier than Start Date.');
        redirect('projects.php'.($id?'?edit='.$id:'?add=1'));
    }

    if ($id) {
        $data[] = $_SESSION['user_id'];
        $data[] = $id;
        $pdo->prepare("UPDATE projects SET project_name=?, location=?, client=?, general_contractor=?, consultant=?, project_manager=?, start_date=?, end_date=?, status=?, updated_by=?, updated_at=NOW() WHERE id=?")->execute($data);
        audit_event($pdo,'update','projects',$id,$id,'Project updated: '.$projectName);
        flash('success', 'Project updated successfully.');
    } else {
        $data[] = $_SESSION['user_id'];
        $pdo->prepare("INSERT INTO projects (project_name, location, client, general_contractor, consultant, project_manager, start_date, end_date, status, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute($data);
        $id=(int)$pdo->lastInsertId();
        (new App\Services\ProjectRoleAssignmentService($pdo, project_authorization($pdo)))
            ->bootstrapProjectManager($id, (int)$_SESSION['user_id']);
        audit_event($pdo,'create','projects',$id,$id,'Project created: '.$projectName);
        flash('success', 'Project added successfully.');
    }
    redirect('projects.php');
}

$q = trim($_GET['q'] ?? '');
$scope = project_scope_clause('p.id');
$showForm = isset($_GET['add']) ? has_role('admin','project_manager') : (can_manage_projects() && isset($_GET['edit']));
$edit = null;
if (can_manage_projects() && isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM projects WHERE id=?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
    if($edit)require_project_permission($pdo,(int)$edit['id'],'project.edit');
    if (!$edit) { $showForm = false; }
}

if ($q !== '') {
    $st = $pdo->prepare("SELECT p.*, COUNT(pp.id) progress_items, CASE WHEN COALESCE(SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END),0)<=0 THEN 0.00 WHEN SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight*pp.percentage_complete ELSE 0 END)>=SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END)*100 THEN 100.00 ELSE LEAST(99.99,ROUND(SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight*pp.percentage_complete ELSE 0 END)/SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END),2)) END overall_progress
        FROM projects p LEFT JOIN project_progress pp ON pp.project_id=p.id
        WHERE (p.project_name LIKE ? OR p.location LIKE ? OR p.client LIKE ? OR p.general_contractor LIKE ? OR p.consultant LIKE ? OR p.project_manager LIKE ?) AND $scope
        GROUP BY p.id ORDER BY p.id DESC");
    $like = "%$q%";
    $st->execute([$like,$like,$like,$like,$like,$like]);
    $rows = $st->fetchAll();
} else {
    $rows = $pdo->query("SELECT p.*, COUNT(pp.id) progress_items, CASE WHEN COALESCE(SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END),0)<=0 THEN 0.00 WHEN SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight*pp.percentage_complete ELSE 0 END)>=SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END)*100 THEN 100.00 ELSE LEAST(99.99,ROUND(SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight*pp.percentage_complete ELSE 0 END)/SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END),2)) END overall_progress
        FROM projects p LEFT JOIN project_progress pp ON pp.project_id=p.id WHERE $scope GROUP BY p.id ORDER BY p.id DESC")->fetchAll();
}

foreach($rows as &$projectRow){
    $projectRow['can_edit_project']=can_project_permission($pdo,(int)$projectRow['id'],'project.edit');
    $projectRow['can_manage_assignments']=can_project_permission($pdo,(int)$projectRow['id'],'assignment.manage');
}
unset($projectRow);

$pageTitle = 'Projects';
