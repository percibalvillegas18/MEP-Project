<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_login();

$priorities = ['Complete','High','Medium','Low'];
$formPriorities = ['High','Medium','Low']; // BOQ add form — no "Complete"
$statuses = ['Not Started','In Progress','Not Applicable','Complete','Not Scheduled','Completed','Near Due','Over Due','MAS-Under Review','Proceed to Procurement','MAS Resubmit','MAS Rejected','Procurement Pending','Procurement RFQ for Approval','Procurement PO Release','Procurement Payment on Process','Procurement on Process','Material Delivered'];
$unitOptions = ['No.','m','m²','Ls','Lot','kg','coil'];
$startSources = ['Not Set','Material Submittal','Procurement','Site Work','Manual'];
$endSources = ['Not Set','Completion Approval','Testing/Handover','Manual'];
$projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
if (!$projectId) { flash('error','Select a project first.'); redirect('projects.php'); }
require_project_permission($pdo,$projectId,$_SERVER['REQUEST_METHOD']==='POST'?'progress.update':'project.view');

$st = $pdo->prepare("SELECT * FROM projects WHERE id=?"); $st->execute([$projectId]); $project = $st->fetch();
if (!$project) { flash('error','Project not found.'); redirect('projects.php'); }

// Load discipline list from disciplines table
$disciplineList = $pdo->query("SELECT dis_name FROM disciplines ORDER BY dis_name ASC")->fetchAll(PDO::FETCH_COLUMN);

function valid_hex_color(string $value, string $fallback): string {
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? strtoupper($value) : $fallback;
}

function task_description_parts(?string $description): array {
    $parts = ['objective'=>'', 'deliverables'=>'', 'specifications'=>'', 'references'=>'', 'requirements'=>'', 'acceptance'=>''];
    $description = trim((string)$description);
    if ($description === '') return $parts;

    $labels = 'Objective|Requirements?(?:\s*\/\s*Specifications?)?|Remarks?\s*\/\s*References?|Key\s+Deliverables?|Acceptance\s+Criteria';
    $description = preg_replace('/\s+(?=(?:'.$labels.')\s*[:\-])/i', "\n", str_replace(["\r\n", "\r"], "\n", $description));
    $current = 'objective';
    foreach (preg_split('/\n+/', (string)$description) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if (preg_match('/^('.$labels.')\s*[:\-]+\s*(.*)$/i', $line, $match)) {
            $label = strtolower(preg_replace('/\s+/', ' ', trim($match[1])));
            if (str_starts_with($label, 'key deliverable')) $current = 'deliverables';
            elseif (str_starts_with($label, 'remarks')) $current = 'references';
            elseif (str_starts_with($label, 'acceptance')) $current = 'acceptance';
            elseif (str_starts_with($label, 'requirements') && str_contains($label, '/')) $current = 'specifications';
            elseif (str_starts_with($label, 'requirements')) $current = 'requirements';
            else $current = 'objective';
            $line = trim($match[2]);
            if ($line === '') continue;
        }
        $parts[$current] .= ($parts[$current] === '' ? '' : "\n").$line;
    }
    return array_map('trim', $parts);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!can_project_permission($pdo,$projectId,'progress.update')){http_response_code(403);exit('Access denied. Progress update permission is required.');}
    verify_csrf();
    $action = $_POST['action'] ?? 'add_progress';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete_progress') {
        require_admin();
        $pdo->prepare("DELETE FROM project_progress WHERE id=? AND project_id=?")->execute([$id,$projectId]);
        audit_event($pdo,'delete','project_progress',$id,$projectId,'BOQ progress item deleted');
        flash('success','Progress item deleted.'); redirect('project_progress.php?project_id='.$projectId);
    }

    if ($action === 'inline_update') {
        $target=$pdo->prepare("SELECT percentage_complete,measurement_profile FROM project_progress WHERE id=? AND project_id=?");
        $target->execute([$id,$projectId]); $target=$target->fetch();
        if(!$target){http_response_code(404);exit('Progress item not found.');}
        $priority = in_array($_POST['priority'] ?? '', $priorities, true) ? $_POST['priority'] : 'Medium';
        $status = in_array($_POST['status'] ?? '', $statuses, true) ? $_POST['status'] : 'In Progress';
        $rawPct=max(0,min(100,(float)($_POST['percentage_complete']??0)));$pct=$rawPct>=100?100.00:min(99.99,round($rawPct,2));
        if(!has_role('admin','project_manager') || ($target['measurement_profile']??'Manual')!=='Manual') $pct=(float)$target['percentage_complete'];
        $remarks = trim($_POST['remarks'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        /* Auto-adjust percentage based on status */
        if ($status === 'MAS-Under Review') { $pct = min(100, $pct + 2); }
        elseif ($status === 'Proceed to Procurement') { $pct = 5; }
        elseif ($status === 'Procurement PO Release') { $pct = 10; }
        elseif ($status === 'Procurement on Process') { $pct = 25; }
        elseif ($status === 'Material Delivered') { $pct = 50; }
        if ($pct === 0) { $status = 'Not Started'; }
        $actualEndSql = (($status === 'Complete' || $status === 'Completed') && $pct === 100)
            ? ", actual_end_date=COALESCE(actual_end_date,CURDATE()), end_date_source=IF(end_date_source='Not Set','Completion Approval',end_date_source)"
            : (($pct < 100) ? ", actual_end_date=NULL, end_date_source='Not Set'" : '');
        $pdo->prepare("UPDATE project_progress SET priority=?, percentage_complete=?, status=?, remarks=?, notes=?, updated_by=?{$actualEndSql} WHERE id=? AND project_id=?")
            ->execute([$priority,$pct,$status,$remarks,$notes,$_SESSION['user_id'],$id,$projectId]);
        audit_event($pdo,'update','project_progress',$id,$projectId,'Inline progress update: '.$status.' / '.$pct.'%');
        $inlineDisc = trim($_POST['inline_discipline'] ?? '');
        $discParam = $inlineDisc !== '' ? '&discipline='.urlencode($inlineDisc) : '';
        flash('success','Progress item updated.'); redirect('project_progress.php?project_id='.$projectId.$discParam.'#tracker');
    }

    $discipline = strtoupper(trim($_POST['discipline'] ?? ''));
    $priority = in_array($_POST['priority'] ?? '', $priorities, true) ? $_POST['priority'] : 'Medium';
    $status = in_array($_POST['status'] ?? '', $statuses, true) ? $_POST['status'] : 'Not Started';
    $task = trim($_POST['task'] ?? '');
    if ($discipline === '' || $task === '') { flash('error','DISCIPLINE and Task are required.'); redirect('project_progress.php?project_id='.$projectId); }
    $qtyRaw = trim($_POST['material_quantity'] ?? '');
    $qty = $qtyRaw === '' ? null : (float)$qtyRaw;
    $unit = trim($_POST['unit'] ?? '');
    $itemTypes=['Heading','Group','Measurable Item'];
    $itemType=in_array($_POST['item_type']??'', $itemTypes, true)?$_POST['item_type']:'Measurable Item';
    $profiles=['Manual','Profile A (Multi-Stage)','Profile B (Single-Stage)','Profile C (T&C Only)','Profile D (Quantity-Based)'];
    $measurementProfile=in_array($_POST['measurement_profile']??'', $profiles, true)?$_POST['measurement_profile']:'Manual';
    $activityWeight=$itemType==='Measurable Item'?max(0.01,min(100,(float)($_POST['activity_weight']??1))):0;
    $rawPlanned=$itemType==='Measurable Item'?max(0,min(100,(float)($_POST['planned_percentage']??0))):0;$plannedPct=$rawPlanned>=100?100.00:min(99.99,round($rawPlanned,2));
    $plannedStart=($_POST['planned_start_date']??'')?:null;
    $plannedEnd=($_POST['planned_end_date']??'')?:null;
    $actualStart=($_POST['actual_start_date']??'')?:null;
    $actualEnd=($_POST['actual_end_date']??'')?:null;
    $completionDateSource=$actualEnd?'manual':'auto';
    $installationStart=($_POST['installation_start_date']??'')?:null;
    $startSource=in_array($_POST['start_date_source']??'', $startSources, true)?$_POST['start_date_source']:'Not Set';
    $endSource=in_array($_POST['end_date_source']??'', $endSources, true)?$_POST['end_date_source']:'Not Set';
    $sourceReference=trim($_POST['start_source_reference']??'');
    foreach([$plannedStart,$plannedEnd,$actualStart,$actualEnd,$installationStart] as $dateValue){if(!valid_date_value($dateValue)){flash('error','All dates must use YYYY-MM-DD format.');redirect('project_progress.php?project_id='.$projectId);}}
    if($qty!==null&&$qty<0){flash('error','Material Quantity cannot be negative.');redirect('project_progress.php?project_id='.$projectId);}
    if($plannedStart && $plannedEnd && $plannedEnd<$plannedStart){flash('error','Planned End cannot be earlier than Planned Start.');redirect('project_progress.php?project_id='.$projectId);}
    if($actualStart && $actualEnd && $actualEnd<$actualStart){flash('error','Actual End cannot be earlier than Actual Start.');redirect('project_progress.php?project_id='.$projectId);}
    if($actualEnd){$completionPct=0;if($id){$cp=$pdo->prepare("SELECT percentage_complete FROM project_progress WHERE id=? AND project_id=?");$cp->execute([$id,$projectId]);$completionPct=(int)$cp->fetchColumn();}if(!in_array($status,['Complete','Completed'],true)||$completionPct<100){flash('error','Actual End requires 100% progress and Complete/Completed status.');redirect('project_progress.php?project_id='.$projectId);}}
    if($actualStart && $startSource==='Not Set')$startSource='Manual';
    if($actualEnd && $endSource==='Not Set')$endSource='Manual';

    if ($action === 'edit_progress') {
        $oldReferenceStatement=$pdo->prepare("SELECT boq_no FROM project_progress WHERE id=? AND project_id=?");
        $oldReferenceStatement->execute([$id,$projectId]);
        $oldBoqNo=(string)($oldReferenceStatement->fetchColumn()?:'');
        $newBoqNo=trim($_POST['boq_no']??'');
        $pdo->prepare("UPDATE project_progress SET discipline=?, priority=?, item_type=?, activity_weight=?, measurement_profile=?, planned_percentage=?, boq_no=?, task=?, material_description=?, material_quantity=?, unit=?, planned_start_date=?, planned_end_date=?, actual_start_date=?, actual_end_date=?, completion_date_source=?, start_date_source=?, start_source_reference=?, installation_start_date=?, end_date_source=?, status=?, notes=?, updated_by=? WHERE id=? AND project_id=?")
            ->execute([$discipline,$priority,$itemType,$activityWeight,$measurementProfile,$plannedPct,$newBoqNo,$task,trim($_POST['material_description'] ?? ''),$qty,$unit,$plannedStart,$plannedEnd,$actualStart,$actualEnd,$completionDateSource,$startSource,$sourceReference,$installationStart,$endSource,$status,trim($_POST['notes'] ?? ''),$_SESSION['user_id'],$id,$projectId]);
        if($oldBoqNo!==''&&!hash_equals($oldBoqNo,$newBoqNo))recalculate_boq_progress($pdo,$projectId,$oldBoqNo);
        if($newBoqNo!=='')recalculate_boq_progress($pdo,$projectId,$newBoqNo);
        audit_event($pdo,'update','project_progress',$id,$projectId,'BOQ item updated: '.$task);
        flash('success','BOQ item updated successfully.');
        redirect('project_progress.php?project_id='.$projectId.'&discipline='.urlencode($discipline).'#tracker');
    }

    $pdo->prepare("INSERT INTO project_progress (discipline,priority,item_type,activity_weight,measurement_profile,planned_percentage,boq_no,task,material_description,material_quantity,unit,planned_start_date,planned_end_date,actual_start_date,actual_end_date,completion_date_source,start_date_source,start_source_reference,installation_start_date,end_date_source,status,notes,created_by,project_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([$discipline,$priority,$itemType,$activityWeight,$measurementProfile,$plannedPct,trim($_POST['boq_no'] ?? ''),$task,trim($_POST['material_description'] ?? ''),$qty,$unit,$plannedStart,$plannedEnd,$actualStart,$actualEnd,$completionDateSource,$startSource,$sourceReference,$installationStart,$endSource,$status,trim($_POST['notes'] ?? ''),$_SESSION['user_id'],$projectId]);
    audit_event($pdo,'create','project_progress',(int)$pdo->lastInsertId(),$projectId,'BOQ item created: '.$task);
    flash('success','BOQ item added successfully.');
    redirect('project_progress.php?project_id='.$projectId.'&discipline='.urlencode($discipline).'#tracker');
}

$edit = null;
if (isset($_GET['edit_id'])) {
    $editSt = $pdo->prepare("SELECT * FROM project_progress WHERE id=? AND project_id=?");
    $editSt->execute([(int)$_GET['edit_id'], $projectId]);
    $edit = $editSt->fetch();
}

$discFilter = strtoupper(trim($_GET['discipline'] ?? ''));
$sql="SELECT * FROM project_progress WHERE project_id=?"; $args=[$projectId];
if ($discFilter!=='') { $sql.=" AND discipline=?"; $args[]=$discFilter; }
$sql.=" ORDER BY discipline ASC, id ASC";
$st=$pdo->prepare($sql); $st->execute($args); $rows=$st->fetchAll();
foreach ($rows as &$descriptionRow) $descriptionRow['description_parts'] = task_description_parts($descriptionRow['material_description'] ?? '');
unset($descriptionRow);
$disciplines=$pdo->prepare("SELECT discipline, CASE WHEN SUM(activity_weight)<=0 THEN 0.00 WHEN SUM(activity_weight*percentage_complete)>=SUM(activity_weight)*100 THEN 100.00 ELSE LEAST(99.99,ROUND(SUM(activity_weight*percentage_complete)/SUM(activity_weight),2)) END progress, CASE WHEN SUM(activity_weight)<=0 THEN 0.00 WHEN SUM(activity_weight*planned_percentage)>=SUM(activity_weight)*100 THEN 100.00 ELSE LEAST(99.99,ROUND(SUM(activity_weight*planned_percentage)/SUM(activity_weight),2)) END planned, COUNT(*) total FROM project_progress WHERE project_id=? AND item_type='Measurable Item' GROUP BY discipline ORDER BY discipline"); $disciplines->execute([$projectId]); $disciplineSummary=$disciplines->fetchAll();
$overallSt=$pdo->prepare("SELECT CASE WHEN COALESCE(SUM(activity_weight),0)<=0 THEN 0.00 WHEN SUM(activity_weight*percentage_complete)>=SUM(activity_weight)*100 THEN 100.00 ELSE LEAST(99.99,ROUND(SUM(activity_weight*percentage_complete)/SUM(activity_weight),2)) END FROM project_progress WHERE project_id=? AND item_type='Measurable Item'"); $overallSt->execute([$projectId]); $overall=(float)$overallSt->fetchColumn();
$plannedSt=$pdo->prepare("SELECT CASE WHEN COALESCE(SUM(activity_weight),0)<=0 THEN 0.00 WHEN SUM(activity_weight*planned_percentage)>=SUM(activity_weight)*100 THEN 100.00 ELSE LEAST(99.99,ROUND(SUM(activity_weight*planned_percentage)/SUM(activity_weight),2)) END FROM project_progress WHERE project_id=? AND item_type='Measurable Item'"); $plannedSt->execute([$projectId]); $planned=(float)$plannedSt->fetchColumn(); $variance=round($overall-$planned,2);
$summary=['measurable'=>0,'complete'=>0,'in_progress'=>0,'behind'=>0];
foreach($rows as $summaryRow){if(($summaryRow['item_type']??'Measurable Item')!=='Measurable Item')continue;$summary['measurable']++;$actual=(int)$summaryRow['percentage_complete'];$plan=(int)$summaryRow['planned_percentage'];if($actual>=100)$summary['complete']++;elseif($actual>0)$summary['in_progress']++;if($actual<$plan)$summary['behind']++;}

function priority_class(string $p): string { return 'priority-'.strtolower($p); }
function status_class(string $s): string { return 'status-'.strtolower(str_replace([' ','&'],['-','and'],$s)); }
function pct_class(float $p): string { if($p>=100)return 'pct-100'; if($p>=76)return 'pct-76'; if($p>=51)return 'pct-51'; if($p>=26)return 'pct-26'; return 'pct-0'; }
function total_days($start,$end): string { if(!$start||!$end)return '-'; try{$a=new DateTime($start);$b=new DateTime($end);return (string)($a->diff($b)->days+1);}catch(Exception $e){return '-';} }

$canEditPct = has_role('admin','project_manager');
$pageTitle='MEP Project Progress Tracker';
