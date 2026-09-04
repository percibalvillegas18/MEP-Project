<?php
$pdo=require_once __DIR__ . '/../config.php';
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header('Location: '.$url); exit; }
function app_version_label(): string { return 'Version '.(defined('APP_VERSION')?APP_VERSION:'007.4'); }
function destroy_login_session(): void {
    $_SESSION=[];
    if (ini_get('session.use_cookies')) {
        $p=session_get_cookie_params();
        setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);
    }
    session_destroy();
}
function request_id(): string { static $id=null; if($id!==null)return$id; try{return$id=strtoupper(bin2hex(random_bytes(8)));}catch(Throwable){return$id=strtoupper(substr(hash('sha256',uniqid('',true)),0,16));} }
function refresh_authenticated_user(): bool {
    global $pdo;
    if (empty($_SESSION['user_id'])) return false;
    if (!empty($_SESSION['last_activity']) && time()-(int)$_SESSION['last_activity']>(defined('SESSION_IDLE_SECONDS')?SESSION_IDLE_SECONDS:7200)) { destroy_login_session(); return false; }
    $s=$pdo->prepare('SELECT name,email,role,status,auth_version FROM users WHERE id=? LIMIT 1');
    $s->execute([(int)$_SESSION['user_id']]);
    $user=$s->fetch();
    if (!$user || $user['status']!=='active' || (int)$user['auth_version']!==(int)($_SESSION['auth_version']??0)) {
        destroy_login_session(); return false;
    }
    $_SESSION['name']=$user['name']; $_SESSION['email']=$user['email']; $_SESSION['role']=$user['role'];
    $_SESSION['last_activity']=time();return true;
}
function require_login(): void { if(!refresh_authenticated_user())redirect('login.php?revoked=1'); }
function require_admin(): void { require_role('admin'); }
function require_role(string ...$roles): void { require_login(); if (!in_array($_SESSION['role']??'', $roles, true)) { http_response_code(403); exit('Access denied. You do not have the required role.'); } }
function has_role(string ...$roles): bool { return in_array($_SESSION['role']??'', $roles, true); }
function project_authorization(PDO $pdo): App\Services\ProjectAuthorization { static $instances=[];$key=spl_object_id($pdo);if(!isset($instances[$key]))$instances[$key]=new App\Services\ProjectAuthorization($pdo);return$instances[$key]; }
function can_manage_projects(): bool { global $pdo;return has_role('admin')||project_authorization($pdo)->canAny('project.edit'); }
function can_manage_progress(): bool { global $pdo;return has_role('admin')||project_authorization($pdo)->canAny('progress.update'); }
function can_manage_submittals(): bool { global $pdo;return has_role('admin')||project_authorization($pdo)->canAny('submittal.create_edit'); }
function can_manage_procurement(): bool { global $pdo;return has_role('admin')||project_authorization($pdo)->canAny('procurement.create_edit'); }
function can_manage_suppliers(): bool { return has_role('admin','project_manager','project_engineer','mep_engineer'); }
function can_access_project(PDO $pdo,int $projectId,bool $edit=false): bool {
    return project_authorization($pdo)->can((int)($_SESSION['user_id']??0),$projectId,$edit?'project.edit':'project.view');
}
function require_project_access(PDO $pdo,int $projectId,bool $edit=false): void { require_login(); if(!can_access_project($pdo,$projectId,$edit)){http_response_code(403);exit('Access denied. You are not assigned to this project.');} }
function can_project_permission(PDO $pdo,int $projectId,string $permission): bool { return project_authorization($pdo)->can((int)($_SESSION['user_id']??0),$projectId,$permission); }
function require_project_permission(PDO $pdo,int $projectId,string $permission): void { require_login(); project_authorization($pdo)->require($projectId,$permission); }
function project_scope_clause(string $projectColumn='p.id'): string {
    global $pdo;return project_authorization($pdo)->scopeClause($projectColumn);
}
function accessible_projects(PDO $pdo): array { $sql='SELECT p.id,p.project_name FROM projects p WHERE '.project_scope_clause('p.id').' ORDER BY p.project_name'; return $pdo->query($sql)->fetchAll(); }
function role_label(string $role): string { return ['admin'=>'Admin','user'=>'User','project_engineer'=>'Project Engineer','mep_engineer'=>'MEP Engineer','project_manager'=>'Project Manager'][$role]??'User'; }
function csrf_token(): string { if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function verify_csrf(): void { $token=$_POST['csrf_token']??''; if (!$token || !hash_equals($_SESSION['csrf_token']??'', $token)) { http_response_code(419); exit('Invalid security token. Please refresh and try again.'); } }
function valid_date_value(?string $value): bool { if ($value===null || $value==='') return true; $d=DateTime::createFromFormat('Y-m-d',$value); return $d && $d->format('Y-m-d')===$value; }
function dates_in_order(?string $start,?string $end): bool { return !$start || !$end || $end >= $start; }
function safe_document_url(?string $value): bool {
    if ($value===null || trim($value)==='') return true;
    $value=trim($value); $parts=parse_url($value);
    return is_array($parts) && isset($parts['scheme']) && in_array(strtolower((string)$parts['scheme']),['http','https'],true) && filter_var($value,FILTER_VALIDATE_URL)!==false;
}
function safe_document_href(?string $value): ?string {
    $value=trim((string)$value);
    return $value!=='' && safe_document_url($value) ? $value : null;
}
function flash(string $type,string $message): void { $_SESSION['flash']=['type'=>$type,'message'=>$message]; }
function pull_flash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
function valid_project_link(PDO $pdo,string $table,?int $id,int $projectId): bool {
    if (!$id) return true; if (!in_array($table,['project_progress','submittals'],true)) return false;
    $st=$pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE id=? AND project_id=?"); $st->execute([$id,$projectId]); return (bool)$st->fetchColumn();
}
function record_workflow_status(PDO $pdo,string $entity,int $entityId,int $projectId,string $type,?string $old,string $new,string $remarks=''): void {
    if ($old===$new || !$entityId) return;
    $s=$pdo->prepare("INSERT INTO workflow_status_history(entity_type,entity_id,project_id,status_type,old_status,new_status,remarks,changed_by) VALUES(?,?,?,?,?,?,?,?)");
    $s->execute([$entity,$entityId,$projectId,$type,$old,$new,$remarks,(int)$_SESSION['user_id']]);
}
function audit_event(PDO $pdo,string $action,string $module,?int $recordId=null,?int $projectId=null,string $description=''): void {
    try { $cut=static fn(string $v,int $n):string=>function_exists('mb_substr')?mb_substr($v,0,$n):substr($v,0,$n); $s=$pdo->prepare("INSERT INTO audit_logs(user_id,project_id,action,module,record_id,description,ip_address,user_agent) VALUES(?,?,?,?,?,?,?,?)");
        $s->execute([$_SESSION['user_id']??null,$projectId,$action,$module,$recordId,$cut($description,500),$_SERVER['REMOTE_ADDR']??'',$cut($_SERVER['HTTP_USER_AGENT']??'',255)]);
    } catch (Throwable $e) { error_log('Audit log unavailable: '.$e->getMessage()); }
}
function evidence_storage(): App\Services\EvidenceStorage {static $storage=null;return $storage??=new App\Services\EvidenceStorage();}
function evidence_url(?string $name):string{return evidence_storage()->url((string)$name);}
function remove_or_queue_evidence(PDO $pdo,string $name,?string $checksum,string $reason):void{
    if($name===''||evidence_storage()->delete($name))return;
    try{$key=hash('sha256',$name.'|'.($checksum??'').'|'.$reason);$s=$pdo->prepare("INSERT INTO file_cleanup_queue(relative_path,checksum,reason,idempotency_key,status,last_error,next_attempt_at) VALUES(?,?,?,?, 'pending','object deletion failed after commit',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE last_error=VALUES(last_error),next_attempt_at=LEAST(COALESCE(next_attempt_at,VALUES(next_attempt_at)),VALUES(next_attempt_at))");$s->execute([$name,$checksum,$reason,$key]);}catch(Throwable $e){error_log('Evidence cleanup could not be queued: '.$e->getMessage());}
}
function remove_or_queue_file(PDO $pdo,string $absolutePath,string $relativePath,?string $checksum,string $reason): void {
    if(!is_file($absolutePath)||@unlink($absolutePath))return;
    try{$key=hash('sha256',$relativePath.'|'.($checksum??'').'|'.$reason);$s=$pdo->prepare("INSERT INTO file_cleanup_queue(relative_path,checksum,reason,idempotency_key,status,last_error,next_attempt_at) VALUES(?,?,?,?, 'pending','unlink failed after commit',UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE last_error=VALUES(last_error),next_attempt_at=LEAST(COALESCE(next_attempt_at,VALUES(next_attempt_at)),VALUES(next_attempt_at))");$s->execute([$relativePath,$checksum,$reason,$key]);}
    catch(Throwable $e){error_log('Evidence cleanup could not be queued: '.$e->getMessage());}
}
function sync_progress_by_boq(PDO $pdo,int $projectId,string $boqNo,string $status,?int $percentage=null): bool {
    $s=$pdo->prepare("SELECT id FROM project_progress WHERE project_id=? AND boq_no=? AND item_type='Measurable Item'");$s->execute([$projectId,$boqNo]);$ids=$s->fetchAll(PDO::FETCH_COLUMN);
    if(count($ids)!==1){audit_event($pdo,'sync_skipped','project_progress',null,$projectId,'BOQ sync skipped because '.$boqNo.' matched '.count($ids).' measurable items');return false;}
    if($percentage===null){$u=$pdo->prepare("UPDATE project_progress SET status=?,updated_by=?,updated_at=NOW() WHERE id=?");$u->execute([$status,$_SESSION['user_id'],$ids[0]]);}
    else{$u=$pdo->prepare("UPDATE project_progress SET status=?,percentage_complete=?,updated_by=?,updated_at=NOW() WHERE id=?");$u->execute([$status,$percentage,$_SESSION['user_id'],$ids[0]]);} return true;
}

function recalculate_progress_actual_start(PDO $pdo,int $projectId,string $boqNo): void {
    if ($projectId<1 || $boqNo==='') return;
    $p=$pdo->prepare("SELECT id,actual_start_date,start_date_source FROM project_progress WHERE project_id=? AND boq_no=? AND item_type='Measurable Item'");
    $p->execute([$projectId,$boqNo]); $matches=$p->fetchAll();
    if(count($matches)!==1) return;
    $s=$pdo->prepare("SELECT submitted_date,submittal_reference FROM submittals WHERE project_id=? AND boq_ref_no=? AND submitted_date IS NOT NULL ORDER BY submitted_date,id LIMIT 1");
    $s->execute([$projectId,$boqNo]); $earliest=$s->fetch(); $row=$matches[0];
    if($earliest && (empty($row['actual_start_date']) || in_array($row['start_date_source'],['Not Set','Material Submittal',''],true))){
        $pdo->prepare("UPDATE project_progress SET actual_start_date=?,start_date_source='Material Submittal',start_source_reference=?,updated_by=?,updated_at=NOW() WHERE id=?")
            ->execute([$earliest['submitted_date'],$earliest['submittal_reference'],$_SESSION['user_id'],$row['id']]);
    } elseif(!$earliest && $row['start_date_source']==='Material Submittal') {
        $pdo->prepare("UPDATE project_progress SET actual_start_date=NULL,start_date_source='Not Set',start_source_reference=NULL,updated_by=?,updated_at=NOW() WHERE id=?")
            ->execute([$_SESSION['user_id'],$row['id']]);
    }
}


function recalculate_boq_progress(PDO $pdo, int $projectId, string $boqNo): void {
    if ($boqNo === '') return;
    $s = $pdo->prepare("SELECT * FROM project_progress WHERE project_id=? AND boq_no=? AND item_type='Measurable Item'");
    $s->execute([$projectId, $boqNo]);
    $boqs = $s->fetchAll();
    if(count($boqs)!==1){audit_event($pdo,'calculation_skipped','project_progress',null,$projectId,'BOQ calculation skipped because '.$boqNo.' matched '.count($boqs).' measurable items');return;}

    $sMas = $pdo->prepare("SELECT status FROM submittals WHERE project_id=? AND boq_ref_no=?");
    $sMas->execute([$projectId, $boqNo]);
    $masStatuses = $sMas->fetchAll(PDO::FETCH_COLUMN);
    $masApproved = in_array('A', $masStatuses) || in_array('B', $masStatuses);

    $sProc = $pdo->prepare("SELECT status FROM procurement WHERE project_id=? AND boq_ref_no=?");
    $sProc->execute([$projectId, $boqNo]);
    $procStatuses = $sProc->fetchAll(PDO::FETCH_COLUMN);
    $procPO = in_array('Purchase Order (PO) Issued', $procStatuses) || in_array('Delivery Expected', $procStatuses) || in_array('Good Received / Delivered', $procStatuses);
    $procDelivered = in_array('Good Received / Delivered', $procStatuses);

    $sWp = $pdo->prepare("SELECT work_plan_stage, MAX(completion_percentage) as max_p FROM workplan WHERE project_id=? AND boq_no=? AND work_plan_status='Work Completed' GROUP BY work_plan_stage");
    $sWp->execute([$projectId, $boqNo]);
    $wpData = [];
    foreach ($sWp->fetchAll() as $row) {
        $wpData[$row['work_plan_stage']] = true;
    }
    // Quantity entries are cumulative installed quantities; MAX prevents the same
    // physical quantity from being counted again at each work-plan stage.
    $sQty=$pdo->prepare("SELECT COALESCE(MAX(installed_quantity),0) FROM workplan WHERE project_id=? AND boq_no=? AND work_plan_status<>'Work Pending'");
    $sQty->execute([$projectId,$boqNo]); $totalQty=(float)$sQty->fetchColumn();

    foreach ($boqs as $boq) {
        $profile = $boq['measurement_profile'] ?? 'Manual';

        $pct = 0.00;
        $totalBoqQty=(float)($boq['material_quantity']??0);
        if($totalBoqQty>0){
            // Approved quantity always determines item progress when available.
            // Activity Weight is reserved for project/discipline roll-up only.
            $rawPct=min(100,max(0,($totalQty/$totalBoqQty)*100));
            $pct=$rawPct>=100?100.00:min(99.99,round($rawPct,2));
            $profile='Installed Quantity';
        } elseif ($profile === 'Manual') {
            continue;
        } elseif ($profile === 'Profile A (Multi-Stage)') {
            if ($masApproved) $pct = 5;
            if ($procPO) $pct = 20;
            if ($procDelivered) $pct = 60;
            if (isset($wpData['First Fix'])) $pct = max($pct, 70);
            if (isset($wpData['Second Fix'])) $pct = max($pct, 80);
            if (isset($wpData['Third/Final Fix'])) $pct = max($pct, 90);
            if (isset($wpData['Testing & Commissioning'])) $pct = max($pct, 95);
            if (isset($wpData['Handover'])) $pct = max($pct, 100);
        } elseif ($profile === 'Profile B (Single-Stage)') {
            if ($masApproved) $pct = 5;
            if ($procPO) $pct = 20;
            if ($procDelivered) $pct = 60;
            if (isset($wpData['First Fix'])) $pct = max($pct, 85);
            if (isset($wpData['Testing & Commissioning'])) $pct = max($pct, 95);
            if (isset($wpData['Handover'])) $pct = max($pct, 100);
        } elseif ($profile === 'Profile C (T&C Only)') {
            if ($masApproved) $pct = 10;
            if (isset($wpData['Testing & Commissioning'])) $pct = max($pct, 80);
            if (isset($wpData['Handover'])) $pct = max($pct, 100);
        } elseif ($profile === 'Profile D (Quantity-Based)') {
            $pct=0.00;
        }

        $status = $boq['status'];
        $pct=round((float)$pct,2);
        if ($pct <= 0) $status = 'Not Started';
        elseif ($pct >= 100.00) $status = 'Complete';
        elseif ($status === 'Not Started' && $pct > 0) $status = 'In Progress';

        $endSql = ($pct===100.00 && empty($boq['actual_end_date'])) ? ", actual_end_date=CURDATE(), end_date_source='Completion Approval', completion_date_source='auto'" : "";
        if ($pct<100.00 && ($boq['completion_date_source']??'auto')==='auto' && ($boq['end_date_source']??'')==='Completion Approval') $endSql = ", actual_end_date=NULL, end_date_source='Not Set'";

        $u = $pdo->prepare("UPDATE project_progress SET percentage_complete=?, status=?, updated_by=?, updated_at=NOW() $endSql WHERE id=?");
        $u->execute([$pct, $status, $_SESSION['user_id'], $boq['id']]);
        if ((int)$boq['percentage_complete'] !== $pct) {
            audit_event($pdo, 'automated_progress', 'project_progress', $boq['id'], $projectId, "Automated progress applied: $pct% via $profile");
        }
    }
}
