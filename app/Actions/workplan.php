<?php
/**
 * MEP Work Plan — Version 007.4
 *
 * Seven-part structure: Setup → POST handler → Data loading →
 * Add/Edit form → Register grid → Modals → JavaScript
 *
 * Fields:
 *   responsible (user_id), activity (from project_progress), discipline (auto),
 *   boq_no (auto), mas_ref (browse submittals by boq_ref_no), work_plan_stage,
 *   work_status_image_before (upload), planned_start, planned_finish,
 *   duration (calculated), remarks, actual_start, actual_finish
 *
 * Stage colors:
 *   First Fix            → #3B82F6 (blue)
 *   Second Fix           → #8B5CF6 (purple)
 *   Third/Final Fix      → #F59E0B (amber)
 *   Testing & Comm.      → #0EA5E9 (sky)
 *   Handover             → #22C55E (green)
 */

/* ============================================================
   1. SETUP
   ============================================================ */
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_login();

$projects = accessible_projects($pdo);
$usersList = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll();

$stages = ['First Fix','Second Fix','Third/Final Fix','Testing & Commissioning','Handover'];

// Work Plan Status and auto-mapped completion percentage
$wpStatuses  = ['Work Pending', 'Working on Progress', 'Work Completed'];
$statusPctMap = [
    'Work Pending'         => 0,
    'Working on Progress'  => 35,
    'Work Completed'       => 100,
];

$stageColors = [
    'First Fix'                 => ['bg' => '#3B82F6', 'fg' => '#fff'],
    'Second Fix'                => ['bg' => '#8B5CF6', 'fg' => '#fff'],
    'Third/Final Fix'           => ['bg' => '#F59E0B', 'fg' => '#172033'],
    'Testing & Commissioning'   => ['bg' => '#0EA5E9', 'fg' => '#fff'],
    'Handover'                  => ['bg' => '#22C55E', 'fg' => '#fff'],
];

$storage=evidence_storage();

/* ============================================================
   2. POST HANDLER
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'add';

    if ($action === 'delete') {
        require_admin();
        $deleteId=(int)($_POST['id']??0);$files=[];$oldRow=null;
        $pdo->beginTransaction();
        try {
            $old=$pdo->prepare("SELECT project_id,boq_no,work_status_image_before,work_status_image_before_checksum,work_status_image_after FROM workplan WHERE id=? FOR UPDATE");
            $old->execute([$deleteId]);$oldRow=$old->fetch();
            if(!$oldRow){$pdo->rollBack();http_response_code(404);exit('Work plan record not found.');}
            require_project_permission($pdo,(int)$oldRow['project_id'],'workplan.create_edit');
            $photoSt=$pdo->prepare("SELECT file_name,checksum FROM workplan_photos WHERE workplan_id=?");$photoSt->execute([$deleteId]);
            if(!empty($oldRow['work_status_image_before']))$files[]=['file_name'=>$oldRow['work_status_image_before'],'checksum'=>$oldRow['work_status_image_before_checksum']??null];
            foreach($photoSt->fetchAll() as $photo)$files[]=$photo;
            $pdo->prepare("DELETE FROM workplan WHERE id=?")->execute([$deleteId]);
            audit_event($pdo,'delete','workplan',$deleteId,(int)$oldRow['project_id'],'Work plan deleted');
            if(!empty($oldRow['boq_no']))recalculate_boq_progress($pdo,(int)$oldRow['project_id'],(string)$oldRow['boq_no']);
            $pdo->commit();
        } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack();throw $e; }
        // Database deletion and progress recalculation are durable before evidence is removed.
        foreach($files as $file){remove_or_queue_evidence($pdo,(string)$file['file_name'],$file['checksum']??null,'Work Plan record deleted');}
        flash('success', 'Work plan record deleted.');
        redirect('workplan.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $originalRow=null; $oldStatus=null;
    if($id){$os=$pdo->prepare("SELECT project_id,boq_no,responsible_user_id,work_plan_status,progress_id FROM workplan WHERE id=?");$os->execute([$id]);$originalRow=$os->fetch();if(!$originalRow){http_response_code(404);exit('Work plan record not found.');}$oldStatus=$originalRow['work_plan_status'];require_project_permission($pdo,(int)$originalRow['project_id'],'workplan.create_edit');}
    $projectId       = (int)($_POST['project_id'] ?? 0);
    require_project_permission($pdo,$projectId,'workplan.create_edit');
    // Non-admin users: force responsible person to current logged-in user
    if (has_role('admin')) {
        $responsibleId = (int)($_POST['responsible_user_id'] ?? 0) ?: null;
    } else {
        $responsibleId = $_SESSION['user_id'];
    }
    $progressId      = (int)($_POST['progress_id'] ?? 0) ?: null;
    $discipline      = '';
    $boqNo           = '';
    $masSubmittalId  = (int)($_POST['mas_submittal_id'] ?? 0) ?: null;
    $stage           = $_POST['work_plan_stage'] ?? 'First Fix';
    if (!in_array($stage, $stages, true)) $stage = 'First Fix';
    $plannedStart    = ($_POST['planned_start'] ?? '') ?: null;
    $plannedFinish   = ($_POST['planned_finish'] ?? '') ?: null;
    $actualStart     = ($_POST['actual_start'] ?? '') ?: null;
    $actualFinish    = ($_POST['actual_finish'] ?? '') ?: null;
    $workPlanStatus  = $_POST['work_plan_status'] ?? 'Work Pending';
    if (!in_array($workPlanStatus, $wpStatuses, true)) $workPlanStatus = 'Work Pending';
    $completionPct   = $statusPctMap[$workPlanStatus] ?? 0;
    $remarks         = trim($_POST['remarks'] ?? '');
    $installedQtyRaw = trim((string)($_POST['installed_quantity'] ?? ''));
    if($installedQtyRaw!==''&&!is_numeric($installedQtyRaw)){
        flash('error','Installed quantity must be a valid non-negative number.');
        redirect('workplan.php'.($id?'?edit='.$id:'?add=1'));
    }
    $installedQty=$installedQtyRaw===''?null:(float)$installedQtyRaw;

    if ($id && (int)$originalRow['project_id'] !== $projectId) {
        flash('error', 'An existing work plan cannot be moved to another project.');
        redirect('workplan.php?edit=' . $id);
    }
    if ($id && !has_role('admin','project_manager')) {
        if ((int)$originalRow['responsible_user_id'] !== (int)$_SESSION['user_id']) {
            http_response_code(403); exit('Access denied. You may edit only your assigned work plans.');
        }
    }

    if (!$projectId) {
        flash('error', 'Project is required.');
        redirect('workplan.php' . ($id ? '?edit=' . $id : '?add=1'));
    }
    $progressLookup=$pdo->prepare("SELECT id,boq_no,discipline,material_quantity,measurement_profile FROM project_progress WHERE id=? AND project_id=? AND item_type='Measurable Item'");
    $progressLookup->execute([$progressId,$projectId]);
    $selectedProgress=$progressLookup->fetch();
    if(!$selectedProgress){
        flash('error', 'Select a measurable BOQ activity belonging to this project.');
        redirect('workplan.php' . ($id ? '?edit=' . $id : '?add=1'));
    }
    $boqNo=(string)$selectedProgress['boq_no'];
    $discipline=(string)$selectedProgress['discipline'];
    $duplicateBoq=$pdo->prepare("SELECT COUNT(*) FROM project_progress WHERE project_id=? AND boq_no=? AND item_type='Measurable Item'");$duplicateBoq->execute([$projectId,$boqNo]);
    if((int)$duplicateBoq->fetchColumn()!==1){flash('error','This BOQ reference is duplicated. Resolve the duplicate measurable items before linking a Work Plan.');redirect('workplan.php'.($id?'?edit='.$id:'?add=1'));}
    if($masSubmittalId){
        $masLookup=$pdo->prepare("SELECT id FROM submittals WHERE id=? AND project_id=? AND boq_ref_no=?");
        $masLookup->execute([$masSubmittalId,$projectId,$boqNo]);
        if(!$masLookup->fetchColumn()){
            flash('error','The selected MAS must belong to the project and match the selected BOQ reference.');
            redirect('workplan.php'.($id?'?edit='.$id:'?add=1'));
        }
    }
    foreach ([$plannedStart,$plannedFinish,$actualStart,$actualFinish] as $dateValue) {
        if (!valid_date_value($dateValue)) { flash('error','Dates must use YYYY-MM-DD format.'); redirect('workplan.php'.($id?'?edit='.$id:'?add=1')); }
    }
    if (!dates_in_order($plannedStart,$plannedFinish) || !dates_in_order($actualStart,$actualFinish)) {
        flash('error','Finish dates cannot be earlier than start dates.'); redirect('workplan.php'.($id?'?edit='.$id:'?add=1'));
    }
    if ($installedQty !== null && $installedQty < 0) { flash('error','Installed quantity cannot be negative.'); redirect('workplan.php'.($id?'?edit='.$id:'?add=1')); }
    if($installedQty!==null&&$selectedProgress['measurement_profile']==='Profile D (Quantity-Based)'&&$selectedProgress['material_quantity']!==null&&$installedQty>(float)$selectedProgress['material_quantity']){
        flash('error','Cumulative installed quantity cannot exceed the approved BOQ quantity.');redirect('workplan.php'.($id?'?edit='.$id:'?add=1'));
    }
    if ($workPlanStatus === 'Work Completed' && !$actualFinish) { flash('error','Actual Finish is required when work is completed.'); redirect('workplan.php'.($id?'?edit='.$id:'?add=1')); }

    // Handle one before photo and up to five after photos.
    $imageBefore = '';
    $imageBeforeChecksum = null;
    $imageAfter  = '';
    $existingAfterPhotos=[];
    if ($id) {
        // Keep existing images unless new ones uploaded
        $existImg = $pdo->prepare("SELECT work_status_image_before,work_status_image_before_checksum,work_status_image_after FROM workplan WHERE id=?");
        $existImg->execute([$id]);
        $existRow = $existImg->fetch();
        $imageBefore = $existRow['work_status_image_before'] ?? '';
        $imageBeforeChecksum=$existRow['work_status_image_before_checksum']??null;
        $imageAfter  = $existRow['work_status_image_after'] ?? '';
        $aps=$pdo->prepare("SELECT id,file_name,checksum,sort_order FROM workplan_photos WHERE workplan_id=? ORDER BY sort_order,id");$aps->execute([$id]);$existingAfterPhotos=$aps->fetchAll();
    }
    $oldBeforeName = $imageBefore;
    $oldBeforeChecksum=$imageBeforeChecksum;

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    // Process the single before-work image.
    foreach (['work_status_image_before' => &$imageBefore] as $field => &$imgName) {
        if(!empty($_FILES[$field]['name'])&&($_FILES[$field]['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK){flash('error','Before-work image upload failed with code '.(int)$_FILES[$field]['error'].'.');redirect('workplan.php'.($id?'?edit='.$id:'?add=1'));}
        if (!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            if (!is_uploaded_file((string)$_FILES[$field]['tmp_name']) || (int)$_FILES[$field]['size'] <= 0 || (int)$_FILES[$field]['size'] > MAX_UPLOAD_BYTES) {
                flash('error', 'Image is too large. Maximum size is ' . round(MAX_UPLOAD_BYTES / 1048576) . ' MB.');
                redirect('workplan.php' . ($id ? '?edit=' . $id : '?add=1'));
            }
            $mime = $finfo->file($_FILES[$field]['tmp_name']);
            if (in_array($mime, $allowed, true)) {
                $dimensions = @getimagesize($_FILES[$field]['tmp_name']);
                if (!$dimensions || ($dimensions['mime']??'') !== $mime || $dimensions[0] > 8000 || $dimensions[1] > 8000) {
                    flash('error', 'Invalid image or image dimensions exceed 8000 x 8000 pixels.');
                    redirect('workplan.php' . ($id ? '?edit=' . $id : '?add=1'));
                }
                $ext = match($mime) {
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/gif'  => 'gif',
                    'image/webp' => 'webp',
                    default      => 'jpg'
                };
                // Keep the previous image until the database transaction commits.
                $sourceChecksum=hash_file('sha256',$_FILES[$field]['tmp_name']);
                if(!is_string($sourceChecksum)||strlen($sourceChecksum)!==64){flash('error','Image checksum validation failed.');redirect('workplan.php'.($id?'?edit='.$id:'?add=1'));}
                $imgName = $storage->objectKey('wp_before_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext);
                if (!$storage->putUploaded($_FILES[$field]['tmp_name'],$imgName,$mime,$sourceChecksum)) {
                    flash('error', 'Image upload failed. Please try again.');
                    redirect('workplan.php' . ($id ? '?edit=' . $id : '?add=1'));
                }
                $imageBeforeChecksum=$sourceChecksum;
            } else {
                flash('error', 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP.');
                redirect('workplan.php' . ($id ? '?edit=' . $id : '?add=1'));
            }
        }
    }
    unset($imgName);
    $newBeforeName = ($imageBefore !== $oldBeforeName) ? $imageBefore : '';
    $discardNewBefore = static function() use ($storage, &$newBeforeName): void {
        if ($newBeforeName) { $storage->delete($newBeforeName); $newBeforeName=''; }
    };

    $removeAfterIds=array_values(array_unique(array_map('intval',$_POST['remove_after_photo']??[])));
    $ownedRemove=[];
    foreach($existingAfterPhotos as $photo){if(in_array((int)$photo['id'],$removeAfterIds,true))$ownedRemove[]=$photo;}
    $afterFiles=$_FILES['work_status_images_after']??null;$newAfter=[];
    if($afterFiles&&is_array($afterFiles['name']??null)){
        foreach($afterFiles['name'] as $i=>$original){if($original==='')continue;if(($afterFiles['error'][$i]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK){$discardNewBefore();flash('error','One of the after-work photos could not be uploaded.');redirect('workplan.php'.($id?'?edit='.$id:'?add=1'));}$newAfter[]=['name'=>$original,'tmp'=>$afterFiles['tmp_name'][$i],'size'=>(int)$afterFiles['size'][$i]];}
    }
    if(count($existingAfterPhotos)-count($ownedRemove)+count($newAfter)>5){$discardNewBefore();flash('error','A work plan can contain a maximum of 5 after-work photos.');redirect('workplan.php'.($id?'?edit='.$id:'?add=1'));}
    $newAfterNames=[];$newAfterChecksums=[];
    foreach($newAfter as $upload){
        if(!is_uploaded_file((string)$upload['tmp'])||$upload['size']<=0||$upload['size']>MAX_UPLOAD_BYTES){$discardNewBefore();flash('error','Each image must be a valid upload not exceeding '.round(MAX_UPLOAD_BYTES/1048576).' MB.');redirect('workplan.php'.($id?'?edit='.$id:'?add=1'));}
        $mime=$finfo->file($upload['tmp']);if(!in_array($mime,$allowed,true)){$discardNewBefore();flash('error','Invalid image type. Allowed: JPG, PNG, GIF, WEBP.');redirect('workplan.php'.($id?'?edit='.$id:'?add=1'));}
        $dimensions=@getimagesize($upload['tmp']);if(!$dimensions||($dimensions['mime']??'')!==$mime||$dimensions[0]>8000||$dimensions[1]>8000){$discardNewBefore();flash('error','Invalid image or image dimensions exceed 8000 x 8000 pixels.');redirect('workplan.php'.($id?'?edit='.$id:'?add=1'));}
        $sourceChecksum=hash_file('sha256',$upload['tmp']);if(!is_string($sourceChecksum)||strlen($sourceChecksum)!==64){$discardNewBefore();flash('error','Image checksum validation failed.');redirect('workplan.php'.($id?'?edit='.$id:'?add=1'));}
        $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'][$mime];$name=$storage->objectKey('wp_after_'.time().'_'.bin2hex(random_bytes(5)).'.'.$ext);
        if(!$storage->putUploaded($upload['tmp'],$name,$mime,$sourceChecksum)){$discardNewBefore();foreach($newAfterNames as $created)$storage->delete($created);flash('error','After-work photo upload failed. Please try again.');redirect('workplan.php'.($id?'?edit='.$id:'?add=1'));}$newAfterNames[]=$name;$newAfterChecksums[$name]=$sourceChecksum;
    }

    $pdo->beginTransaction();
    try { if ($id) {
        $pdo->prepare("UPDATE workplan SET project_id=?, responsible_user_id=?, progress_id=?, discipline=?, boq_no=?, mas_submittal_id=?, work_plan_stage=?, work_status_image_before=?,work_status_image_before_checksum=?, work_status_image_after=?, installed_quantity=?, planned_start=?, planned_finish=?, actual_start=?, actual_finish=?, work_plan_status=?, completion_percentage=?, remarks=?, updated_by=?, updated_at=NOW() WHERE id=?")
            ->execute([$projectId, $responsibleId, $progressId, $discipline, $boqNo, $masSubmittalId, $stage, $imageBefore,$imageBeforeChecksum, $imageAfter, $installedQty, $plannedStart, $plannedFinish, $actualStart, $actualFinish, $workPlanStatus, $completionPct, $remarks, $_SESSION['user_id'], $id]);
        flash('success', 'Work plan record updated.');
    } else {
        $pdo->prepare("INSERT INTO workplan (project_id, responsible_user_id, progress_id, discipline, boq_no, mas_submittal_id, work_plan_stage, work_status_image_before,work_status_image_before_checksum, work_status_image_after, installed_quantity, planned_start, planned_finish, actual_start, actual_finish, work_plan_status, completion_percentage, remarks, created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$projectId, $responsibleId, $progressId, $discipline, $boqNo, $masSubmittalId, $stage, $imageBefore,$imageBeforeChecksum, $imageAfter, $installedQty, $plannedStart, $plannedFinish, $actualStart, $actualFinish, $workPlanStatus, $completionPct, $remarks, $_SESSION['user_id']]);
        $id=(int)$pdo->lastInsertId();
        flash('success', 'Work plan record added.');
    }
    if($ownedRemove){$ids=array_column($ownedRemove,'id');$marks=implode(',',array_fill(0,count($ids),'?'));$pdo->prepare("DELETE FROM workplan_photos WHERE workplan_id=? AND id IN ($marks)")->execute(array_merge([$id],$ids));}
    $sort=(int)$pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM workplan_photos WHERE workplan_id=".(int)$id)->fetchColumn();$ins=$pdo->prepare("INSERT INTO workplan_photos(workplan_id,photo_type,file_name,checksum,sort_order,uploaded_by) VALUES(?,'after',?,?,?,?)");foreach($newAfterNames as $name){$ins->execute([$id,$name,$newAfterChecksums[$name]??null,++$sort,$_SESSION['user_id']]);}
    $first=$pdo->prepare("SELECT file_name FROM workplan_photos WHERE workplan_id=? ORDER BY sort_order,id LIMIT 1");$first->execute([$id]);$imageAfter=(string)($first->fetchColumn()?:'');$pdo->prepare("UPDATE workplan SET work_status_image_after=? WHERE id=?")->execute([$imageAfter,$id]);
    record_workflow_status($pdo,'workplan',$id,$projectId,'Work Plan',$oldStatus,$workPlanStatus,$remarks);
    audit_event($pdo,$oldStatus===null?'create':'update','workplan',$id,$projectId,'Work plan status: '.$workPlanStatus);
    if($originalRow && (!hash_equals((string)$originalRow['boq_no'],$boqNo) || (int)$originalRow['project_id']!==$projectId)){
        recalculate_boq_progress($pdo,(int)$originalRow['project_id'],(string)$originalRow['boq_no']);
    }
    recalculate_boq_progress($pdo,$projectId,$boqNo);
    $pdo->commit();
    if($newBeforeName&&$oldBeforeName)remove_or_queue_evidence($pdo,$oldBeforeName,$oldBeforeChecksum,'Work Plan before photo replaced');
    foreach($ownedRemove as $photo)remove_or_queue_evidence($pdo,(string)$photo['file_name'],$photo['checksum']??null,'Work Plan photo removed');
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();foreach(array_merge($newAfterNames,$newBeforeName?[$newBeforeName]:[]) as $name)$storage->delete($name);throw $e;}

    redirect('workplan.php');
}

/* ============================================================
   3. DATA LOADING
   ============================================================ */
$showForm = isset($_GET['add']) || isset($_GET['edit']);
$edit = null;
$editAfterPhotos = [];
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM workplan WHERE id=?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
    if ($edit) require_project_permission($pdo,(int)$edit['project_id'],'workplan.create_edit');
    if ($edit && !has_role('admin','project_manager') && (int)$edit['responsible_user_id'] !== (int)$_SESSION['user_id']) {
        http_response_code(403); exit('Access denied. You may edit only your assigned work plans.');
    }
    if($edit){$ep=$pdo->prepare("SELECT id,file_name,sort_order FROM workplan_photos WHERE workplan_id=? ORDER BY sort_order,id");$ep->execute([(int)$edit['id']]);$editAfterPhotos=$ep->fetchAll();}
    if (!$edit) $showForm = false;
}

$q = trim($_GET['q'] ?? '');
$filterStage = trim($_GET['stage'] ?? '');
$filterProject = (int)($_GET['project'] ?? 0);

$sql = "SELECT w.*, p.project_name, u.name AS responsible_name
        FROM workplan w
        JOIN projects p ON p.id = w.project_id
        LEFT JOIN users u ON u.id = w.responsible_user_id
        WHERE " . project_scope_clause('p.id');
$params = [];

if ($q !== '') {
    $sql .= " AND (w.discipline LIKE ? OR w.boq_no LIKE ? OR w.remarks LIKE ? OR p.project_name LIKE ? OR u.name LIKE ?)";
    $like = "%$q%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
if ($filterStage !== '') {
    $sql .= " AND w.work_plan_stage = ?";
    $params[] = $filterStage;
}
if ($filterProject > 0) {
    $sql .= " AND w.project_id = ?";
    $params[] = $filterProject;
}

$sql .= " ORDER BY p.project_name ASC, w.discipline ASC, w.id DESC";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

$afterPhotosByWorkplan=[];$workplanIds=array_map('intval',array_column($rows,'id'));
if($workplanIds){$in=implode(',',array_fill(0,count($workplanIds),'?'));$ps=$pdo->prepare("SELECT workplan_id,file_name FROM workplan_photos WHERE workplan_id IN ($in) ORDER BY workplan_id,sort_order,id");$ps->execute($workplanIds);foreach($ps->fetchAll() as $photo)$afterPhotosByWorkplan[(int)$photo['workplan_id']][]=$photo['file_name'];}

// Resolve MAS submittal references for display
$masMap = [];
$masIds = array_filter(array_unique(array_column($rows, 'mas_submittal_id')));
if ($masIds) {
    $in = implode(',', array_map('intval', $masIds));
    $masRows = $pdo->query("SELECT id, submittal_reference, material_description FROM submittals WHERE id IN($in)")->fetchAll();
    foreach ($masRows as $mr) $masMap[$mr['id']] = $mr;
}

// Resolve progress activity task names for display
$progMap = [];
$progIds = array_filter(array_unique(array_column($rows, 'progress_id')));
if ($progIds) {
    $in = implode(',', array_map('intval', $progIds));
    $progRows = $pdo->query("SELECT id, task, material_description FROM project_progress WHERE id IN($in)")->fetchAll();
    foreach ($progRows as $pr) $progMap[$pr['id']] = $pr;
}

// Group rows by project
$grouped = [];
foreach ($rows as $r) {
    $pName = $r['project_name'];
    $grouped[$pName][] = $r;
}

// Build users lookup for form edit
$usersMap = [];
foreach ($usersList as $u) $usersMap[$u['id']] = $u['name'];

$pageTitle = 'MEP Work Plan';
