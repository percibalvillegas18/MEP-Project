<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_login();

$projects = accessible_projects($pdo);
$statuses = ['Not Started','Purchase Order (PO) Issued','Delivery Expected','Good Received / Delivered'];
$currencies=['SAR','USD','EUR','GBP','AED'];
$statusLabels = [
    'Not Started'                => 'Not Started',
    'Purchase Order (PO) Issued' => 'PO Issued',
    'Delivery Expected'          => 'Delivery Expected',
    'Good Received / Delivered'  => 'Delivered',
];
$statusColors = [
    'Not Started'                => ['bg' => '#6B7280', 'fg' => '#fff'],
    'Purchase Order (PO) Issued' => ['bg' => '#8B5CF6', 'fg' => '#fff'],
    'Delivery Expected'          => ['bg' => '#F59E0B', 'fg' => '#172033'],
    'Good Received / Delivered'  => ['bg' => '#22C55E', 'fg' => '#fff'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!can_manage_procurement()){http_response_code(403);exit('Access denied. Procurement edit permission is required.');}
    verify_csrf();
    $action = $_POST['action'] ?? 'add';

    if ($action === 'delete') {
        require_admin();
        $deletedId=(int)$_POST['id'];$ds=$pdo->prepare("SELECT project_id,boq_ref_no,material_description FROM procurement WHERE id=?");$ds->execute([$deletedId]);$deleted=$ds->fetch();
        $pdo->prepare("DELETE FROM procurement WHERE id=?")->execute([(int)$_POST['id']]);
        audit_event($pdo,'delete','procurement',$deletedId,$deleted['project_id']??null,'Procurement deleted: '.($deleted['material_description']??''));
        if(!empty($deleted['project_id']) && !empty($deleted['boq_ref_no'])) recalculate_boq_progress($pdo, (int)$deleted['project_id'], $deleted['boq_ref_no']);
        flash('success', 'Procurement record deleted.');
        redirect('procurement.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $originalRow=null; $oldStatus=null;
    if($id){$os=$pdo->prepare("SELECT project_id,boq_ref_no,status,submittal_reference_id FROM procurement WHERE id=?");$os->execute([$id]);$originalRow=$os->fetch();if(!$originalRow){http_response_code(404);exit('Procurement record not found.');}require_project_permission($pdo,(int)$originalRow['project_id'],'procurement.create_edit');$oldStatus=$originalRow['status'];}
    $submittalRefId = (int)($_POST['submittal_reference_id'] ?? 0) ?: null;
    $boqRefNo = '';
    $procStatus = $_POST['status'] ?? 'Not Started';
    if (!in_array($procStatus, $statuses, true)) $procStatus = 'Not Started';
    $approvedDate=($_POST['approved_date']??'')?:null;
    $poDate=($_POST['po_date']??'')?:null;
    $requiredDate=($_POST['required_date']??'')?:null;
    $expectedDate=($_POST['expected_delivery_date']??'')?:null;
    $actualDate=($_POST['actual_delivery_date']??'')?:null;
    $currency=strtoupper(trim((string)($_POST['currency']??DEFAULT_CURRENCY)));
    if(!in_array($currency,$currencies,true))$currency=DEFAULT_CURRENCY;

    $projectId=(int)($_POST['project_id']??0);
    $selectedSubmittal=null;
    if(!$submittalRefId){
        flash('error','Select an approved MAS reference. Procurement references are derived from that record.');
        redirect('procurement.php'.($id?'?edit='.$id:'?add=1'));
    }
    if($submittalRefId){
        $referenceLookup=$pdo->prepare("SELECT s.id,s.project_id,s.boq_ref_no,s.material_description,s.manufacturer,s.approved_date FROM submittals s JOIN project_progress pp ON pp.id=s.progress_id AND pp.project_id=s.project_id AND pp.boq_no=s.boq_ref_no AND pp.item_type='Measurable Item' WHERE s.id=? AND s.project_id=? AND s.status IN ('A','B') AND (SELECT COUNT(*) FROM project_progress d WHERE d.project_id=s.project_id AND d.boq_no=s.boq_ref_no AND d.item_type='Measurable Item')=1");
        $referenceLookup->execute([$submittalRefId,$projectId]);
        $selectedSubmittal=$referenceLookup->fetch();
        if(!$selectedSubmittal){
            flash('error','The selected MAS is not approved, has a stale BOQ link, or its measurable BOQ reference is duplicated. Re-link the MAS before procurement.');
            redirect('procurement.php'.($id?'?edit='.$id:'?add=1'));
        }
        $boqRefNo=(string)$selectedSubmittal['boq_ref_no'];
        $approvedDate=$selectedSubmittal['approved_date']?:null;
    }
    $d = [
        $projectId,
        $submittalRefId,
        (string)$selectedSubmittal['material_description'],
        (string)$selectedSubmittal['manufacturer'],
        $approvedDate,
        $poDate,
        $requiredDate,
        $expectedDate,
        $actualDate,
        $currency,
        $boqRefNo,
        trim($_POST['supplier'] ?? ''),
        $procStatus,
        trim($_POST['remarks'] ?? ''),
    ];
    require_project_permission($pdo,(int)$d[0],'procurement.create_edit');

    if ($id && (int)$originalRow['project_id'] !== (int)$d[0]) {
        flash('error','An existing procurement record cannot be moved to another project.');
        redirect('procurement.php?edit='.$id);
    }

    if (!$d[0] || $d[2] === '') {
        flash('error', 'Project and material description are required.');
        redirect('procurement.php' . ($id ? '?edit=' . $id : '?add=1'));
    }
    foreach([$approvedDate,$poDate,$requiredDate,$expectedDate,$actualDate] as $dateValue){if(!valid_date_value($dateValue)){flash('error','All dates must use YYYY-MM-DD format.');redirect('procurement.php'.($id?'?edit='.$id:'?add=1'));}}
    if(!dates_in_order($poDate,$expectedDate)||!dates_in_order($poDate,$actualDate)){flash('error','Delivery dates cannot be earlier than the PO Date.');redirect('procurement.php'.($id?'?edit='.$id:'?add=1'));}
    if(in_array($procStatus,['Purchase Order (PO) Issued','Delivery Expected','Good Received / Delivered'],true)&&!$poDate){flash('error','PO Date is required for this procurement status.');redirect('procurement.php'.($id?'?edit='.$id:'?add=1'));}
    if($procStatus==='Delivery Expected'&&!$expectedDate){flash('error','Expected Delivery Date is required.');redirect('procurement.php'.($id?'?edit='.$id:'?add=1'));}
    if($procStatus==='Good Received / Delivered'&&!$actualDate){flash('error','Actual Delivery Date is required for delivered materials.');redirect('procurement.php'.($id?'?edit='.$id:'?add=1'));}

    $pdo->beginTransaction();
    if ($id) {
        $d[] = $_SESSION['user_id'];
        $d[] = $id;
        $pdo->prepare("UPDATE procurement SET project_id=?, submittal_reference_id=?, material_description=?, manufacturer=?, approved_date=?, po_date=?, required_date=?, expected_delivery_date=?, actual_delivery_date=?, currency=?, boq_ref_no=?, supplier=?, status=?, remarks=?, updated_by=?, updated_at=NOW() WHERE id=?")
            ->execute($d);
        flash('success', 'Procurement record updated.');
    } else {
        $d[] = $_SESSION['user_id'];
        $pdo->prepare("INSERT INTO procurement(project_id, submittal_reference_id, material_description, manufacturer, approved_date, po_date, required_date, expected_delivery_date, actual_delivery_date, currency, boq_ref_no, supplier, status, remarks, created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute($d);
        $id=(int)$pdo->lastInsertId();
        flash('success', 'Procurement record added.');
    }
    record_workflow_status($pdo,'procurement',$id,(int)$d[0],$procStatus==='Good Received / Delivered'?'Delivery':'Procurement',$oldStatus,$procStatus,trim($_POST['remarks']??''));
    audit_event($pdo,$oldStatus===null?'create':'update','procurement',$id,(int)$d[0],'Procurement status: '.$procStatus);

    /* --- Sync procurement status → project_progress status --- */
    $projId = (int)$d[0];
    if ($boqRefNo !== '') {
        $statusMap = [
            'Not Started'                => 'Procurement Pending',
            'Purchase Order (PO) Issued' => 'Procurement PO Release',
            'Delivery Expected'          => 'Procurement on Process',
            'Good Received / Delivered'  => 'Material Delivered',
        ];
        if (isset($statusMap[$procStatus])) {
            $s=$pdo->prepare("UPDATE project_progress SET status=?,updated_by=?,updated_at=NOW() WHERE project_id=? AND boq_no=? AND measurement_profile='Manual'");
            $s->execute([$statusMap[$procStatus], $_SESSION['user_id'], $projId, $boqRefNo]);
        }
    }
    if($originalRow && (!hash_equals((string)$originalRow['boq_ref_no'],$boqRefNo) || (int)$originalRow['project_id']!==$projId)){
        recalculate_boq_progress($pdo,(int)$originalRow['project_id'],(string)$originalRow['boq_ref_no']);
    }
    if($boqRefNo!=='') recalculate_boq_progress($pdo,$projId,$boqRefNo);
    $pdo->commit();
    redirect('procurement.php');
}

/* --- Form visibility via URL params (like submittals.php) --- */
$showForm = can_manage_procurement() && (isset($_GET['add']) || isset($_GET['edit']));
$edit = null;
if (can_manage_procurement() && isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM procurement WHERE id=?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
    if ($edit) require_project_permission($pdo,(int)$edit['project_id'],'procurement.create_edit');
    if (!$edit) $showForm = false;
}
$editMasReference = '';
if (!empty($edit['submittal_reference_id'])) {
    $referenceStatement = $pdo->prepare('SELECT submittal_reference FROM submittals WHERE id=?');
    $referenceStatement->execute([$edit['submittal_reference_id']]);
    $editMasReference = (string)($referenceStatement->fetchColumn() ?: $edit['submittal_reference_id']);
}

/* --- Data loading with search/filter --- */
$q = trim($_GET['q'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$sql = "SELECT pr.*, p.project_name
        FROM procurement pr
        JOIN projects p ON p.id = pr.project_id
        WHERE " . project_scope_clause('p.id');
$params = [];

if ($q !== '') {
    $sql .= " AND (pr.material_description LIKE ? OR pr.supplier LIKE ? OR pr.manufacturer LIKE ? OR p.project_name LIKE ? OR pr.boq_ref_no LIKE ?)";
    $like = "%$q%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
if ($filterStatus !== '') {
    $sql .= " AND pr.status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY p.project_name ASC, pr.id DESC";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

/* Resolve submittal_reference for display */
$submittalMap = [];
$subIds = array_filter(array_unique(array_column($rows, 'submittal_reference_id')));
if ($subIds) {
    $in = implode(',', array_map('intval', $subIds));
    $subRows = $pdo->query("SELECT id, submittal_reference FROM submittals WHERE id IN($in)")->fetchAll();
    foreach ($subRows as $sr) $submittalMap[$sr['id']] = $sr['submittal_reference'];
}

/* Group rows by project */
$grouped = [];
foreach ($rows as $r) {
    $pName = $r['project_name'];
    $grouped[$pName][] = $r;
}

$pageTitle = 'Procurement Tracker';
