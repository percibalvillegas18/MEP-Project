<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_login();

$projects = accessible_projects($pdo);
$disciplineList = $pdo->query("SELECT dis_name FROM disciplines ORDER BY dis_name ASC")->fetchAll(PDO::FETCH_COLUMN);
$statusCodes = ['A','B','C','D','UR','P'];
$statusLabels = [
    'A'  => 'Approved',
    'B'  => 'Approved w/ Comments',
    'C'  => 'Resubmit',
    'D'  => 'Rejected',
    'UR' => 'Under Review',
    'P'  => 'Planned'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!can_manage_submittals()){http_response_code(403);exit('Access denied. Submittal edit permission is required.');}
    verify_csrf();
    $action = $_POST['action'] ?? 'add';

    if ($action === 'delete') {
        require_admin();
        $deletedId=(int)$_POST['id'];$ds=$pdo->prepare("SELECT project_id,boq_ref_no,submittal_reference FROM submittals WHERE id=?");$ds->execute([$deletedId]);$deleted=$ds->fetch();
        $pdo->prepare("DELETE FROM submittals WHERE id=?")->execute([(int)$_POST['id']]);
        audit_event($pdo,'delete','submittals',$deletedId,$deleted['project_id']??null,'Submittal deleted: '.($deleted['submittal_reference']??''));
        if(!empty($deleted['project_id']) && !empty($deleted['boq_ref_no'])) { recalculate_progress_actual_start($pdo,(int)$deleted['project_id'],$deleted['boq_ref_no']); recalculate_boq_progress($pdo, (int)$deleted['project_id'], $deleted['boq_ref_no']); }
        flash('success', 'Submittal deleted.');
        redirect('submittals.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $originalRow=null; $oldStatus=null;
    if($id){$os=$pdo->prepare("SELECT project_id,boq_ref_no,status FROM submittals WHERE id=?");$os->execute([$id]);$originalRow=$os->fetch();if(!$originalRow){http_response_code(404);exit('Submittal not found.');}require_project_permission($pdo,(int)$originalRow['project_id'],'submittal.create_edit');$oldStatus=$originalRow['status'];}
    $projectId = (int)($_POST['project_id'] ?? 0);
    require_project_permission($pdo,$projectId,'submittal.create_edit');
    $progressId=(int)($_POST['progress_id']??0);
    $discipline = trim($_POST['discipline'] ?? '');
    $boqRefNo = '';
    $submittalRef = trim($_POST['submittal_reference'] ?? '');
    $materialDesc = trim($_POST['material_description'] ?? '');
    $manufacturer = trim($_POST['manufacturer'] ?? '');
    $countryOrigin = trim($_POST['country_origin'] ?? '');
    $submittalRevNo = trim($_POST['submittal_revision_no'] ?? '');
    $submittedDate = ($_POST['submitted_date'] ?? '') ?: null;
    $approvedDate = ($_POST['approved_date'] ?? '') ?: null;
    $status = $_POST['status'] ?? 'P';
    if (!in_array($status, $statusCodes, true)) $status = 'P';
    $masFileLink = trim($_POST['mas_file_link'] ?? '');
    $consultantComments = trim($_POST['consultant_comments'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!$projectId || $discipline === '') {
        flash('error', 'Project and Discipline are required.');
        redirect('submittals.php' . ($id ? '?edit=' . $id : '?add=1'));
    }
    $boq=$pdo->prepare("SELECT id,boq_no,discipline FROM project_progress WHERE id=? AND project_id=? AND item_type='Measurable Item' AND boq_no<>''");
    $boq->execute([$progressId,$projectId]);$selectedBoq=$boq->fetch();
    if(!$selectedBoq){flash('error','The selected BOQ item is missing or stale. Search and select an active measurable BOQ item again.');redirect('submittals.php'.($id?'?edit='.$id:'?add=1'));}
    $duplicate=$pdo->prepare("SELECT COUNT(*) FROM project_progress WHERE project_id=? AND boq_no=? AND item_type='Measurable Item'");
    $duplicate->execute([$projectId,$selectedBoq['boq_no']]);
    if((int)$duplicate->fetchColumn()!==1){flash('error','This BOQ reference is duplicated. Resolve the duplicate measurable items before creating or editing linked records.');redirect('submittals.php'.($id?'?edit='.$id:'?add=1'));}
    $boqRefNo=(string)$selectedBoq['boq_no'];$discipline=(string)$selectedBoq['discipline'];
    if ($id && (int)$originalRow['project_id'] !== $projectId) { flash('error','An existing submittal cannot be moved to another project.'); redirect('submittals.php?edit='.$id); }
    if (!valid_date_value($submittedDate) || !valid_date_value($approvedDate) || !dates_in_order($submittedDate,$approvedDate)) {
        flash('error','Use valid YYYY-MM-DD dates; Approved Date cannot be earlier than Submitted Date.'); redirect('submittals.php'.($id?'?edit='.$id:'?add=1'));
    }
    if($approvedDate&&!$submittedDate){flash('error','Submitted Date is required when an Approved Date is entered.');redirect('submittals.php'.($id?'?edit='.$id:'?add=1'));}
    if($status!=='P'&&!$submittedDate){flash('error','Submitted Date is required after a submittal leaves Planned status.');redirect('submittals.php'.($id?'?edit='.$id:'?add=1'));}
    if (in_array($status,['A','B'],true) && !$approvedDate) { flash('error','Approved Date is required for approved submittals.'); redirect('submittals.php'.($id?'?edit='.$id:'?add=1')); }
    if (!safe_document_url($masFileLink)) { flash('error','MAS File Link must be a valid HTTP or HTTPS URL.'); redirect('submittals.php'.($id?'?edit='.$id:'?add=1')); }

    $pdo->beginTransaction();
    if ($id) {
        $pdo->prepare("UPDATE submittals SET project_id=?,progress_id=?, discipline=?, boq_ref_no=?, submittal_reference=?, material_description=?, manufacturer=?, country_origin=?, submittal_revision_no=?, submitted_date=?, approved_date=?, status=?, mas_file_link=?, consultant_comments=?, notes=?, updated_by=?, updated_at=NOW() WHERE id=?")
            ->execute([$projectId,$progressId, $discipline, $boqRefNo, $submittalRef, $materialDesc, $manufacturer, $countryOrigin, $submittalRevNo, $submittedDate, $approvedDate, $status, $masFileLink, $consultantComments, $notes, $_SESSION['user_id'], $id]);
        flash('success', 'Submittal updated.');
    } else {
        $pdo->prepare("INSERT INTO submittals (project_id,progress_id, discipline, boq_ref_no, submittal_reference, material_description, manufacturer, country_origin, submittal_revision_no, submitted_date, approved_date, status, mas_file_link, consultant_comments, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$projectId,$progressId, $discipline, $boqRefNo, $submittalRef, $materialDesc, $manufacturer, $countryOrigin, $submittalRevNo, $submittedDate, $approvedDate, $status, $masFileLink, $consultantComments, $notes, $_SESSION['user_id']]);
        $id=(int)$pdo->lastInsertId();
        flash('success', 'Submittal added.');
    }
    record_workflow_status($pdo,'submittal',$id,$projectId,'Submittal',$oldStatus,$status,$consultantComments);
    audit_event($pdo,$oldStatus===null?'create':'update','submittals',$id,$projectId,'MAS status: '.$status.' - '.$submittalRef);

    // Sync submittal status → project_progress status via BOQ Ref. No.
    if ($boqRefNo !== '' && $projectId) {
        $progressStatusMap = [
            'UR' => 'MAS-Under Review',
            'B'  => 'In Progress',
            'A'  => 'Proceed to Procurement',
            'C'  => 'MAS Resubmit',
            'D'  => 'MAS Rejected',
        ];
        if (isset($progressStatusMap[$status])) {
            $s=$pdo->prepare("UPDATE project_progress SET status=?,updated_by=?,updated_at=NOW() WHERE project_id=? AND boq_no=? AND measurement_profile='Manual'");
            $s->execute([$progressStatusMap[$status], $_SESSION['user_id'], $projectId, $boqRefNo]);
        }
        recalculate_progress_actual_start($pdo,$projectId,$boqRefNo);
    }
    if($originalRow && $originalRow['boq_ref_no']!==$boqRefNo) { recalculate_progress_actual_start($pdo,$projectId,$originalRow['boq_ref_no']); recalculate_boq_progress($pdo,$projectId,$originalRow['boq_ref_no']); }
    recalculate_boq_progress($pdo, $projectId, $boqRefNo);
    $pdo->commit();
    redirect('submittals.php');
}

$showForm = can_manage_submittals() && (isset($_GET['add']) || isset($_GET['edit']));
$edit = null;
if (can_manage_submittals() && isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM submittals WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
    if ($edit) require_project_permission($pdo,(int)$edit['project_id'],'submittal.create_edit');
    if (!$edit) $showForm = false;
}

$q = trim($_GET['q'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$sql = "SELECT s.*, p.project_name
        FROM submittals s
        JOIN projects p ON p.id = s.project_id
        WHERE " . project_scope_clause('p.id');
$params = [];

if ($q !== '') {
    $sql .= " AND (s.material_description LIKE ? OR s.manufacturer LIKE ? OR p.project_name LIKE ? OR s.submittal_reference LIKE ? OR s.boq_ref_no LIKE ?)";
    $like = "%$q%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
if ($filterStatus !== '') {
    $sql .= " AND s.status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY p.project_name ASC, s.discipline ASC, s.id ASC";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

// Group rows by project, then by discipline
$grouped = [];
foreach ($rows as $r) {
    $pName = $r['project_name'];
    $disc = $r['discipline'];
    $grouped[$pName][$disc][] = $r;
}

$pageTitle = 'Material Submittals';
