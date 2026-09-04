<?php
namespace App\Services;

use PDO;
use RuntimeException;

final class ProjectRoleAssignmentService
{
    public function __construct(private PDO $db,private ProjectAuthorization $authorization) {}

    public function create(int $projectId,int $userId,string $roleCode,?string $effectiveFrom,?string $effectiveUntil,string $reason,int $actorId): array
    {
        $this->authorization->require($projectId,'assignment.manage');
        $this->validateCommon($projectId,$userId,$roleCode,$effectiveFrom,$effectiveUntil,$reason,$actorId);
        $this->db->beginTransaction();
        try{
            $existing=$this->activeForUpdate($projectId,$userId);
            if($existing)throw new AssignmentConflict('The user already has an active role on this project.');
            $role=$this->role($roleCode);
            $from=$this->dateTime($effectiveFrom)??gmdate('Y-m-d H:i:s');$until=$this->dateTime($effectiveUntil);
            $s=$this->db->prepare("INSERT INTO project_role_assignments(project_id,user_id,role_id,assigned_by,effective_from,effective_until,active,reason) VALUES(?,?,?,?,?,?,1,?)");
            $s->execute([$projectId,$userId,$role['id'],$actorId,$from,$until,$reason]);$id=(int)$this->db->lastInsertId();
            $after=$this->assignmentById($id);
            $this->syncLegacy($projectId,$userId,$role['name'],$this->roleCanEdit((int)$role['id']),$actorId);
            $this->invalidateUser($userId);
            $this->audit('assignment.created',$projectId,$id,$actorId,null,$after,$reason);
            $this->outbox('assignment.created',$id,['project_id'=>$projectId,'user_id'=>$userId,'role_code'=>$roleCode]);
            $this->db->commit();return$after;
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw$e;}
    }

    public function bootstrapProjectManager(int $projectId,int $actorId): void
    {
        if(!$this->authorization->schemaReady()||!in_array($_SESSION['role']??'',['admin','project_manager'],true))return;
        $check=$this->db->prepare("SELECT COUNT(*) FROM projects p WHERE p.id=? AND p.created_by=? AND NOT EXISTS(SELECT 1 FROM project_role_assignments a WHERE a.project_id=p.id AND a.active=1)");$check->execute([$projectId,$actorId]);if(!(int)$check->fetchColumn())return;
        $this->db->beginTransaction();try{$role=$this->role('project_manager');$s=$this->db->prepare("INSERT INTO project_role_assignments(project_id,user_id,role_id,assigned_by,active,reason) VALUES(?,?,?,?,1,?)");$reason='Automatically assigned as creator of the project';$s->execute([$projectId,$actorId,$role['id'],$actorId,$reason]);$id=(int)$this->db->lastInsertId();$after=$this->assignmentById($id);$this->syncLegacy($projectId,$actorId,$role['name'],true,$actorId);$this->audit('assignment.created',$projectId,$id,$actorId,null,$after,$reason);$this->outbox('assignment.created',$id,['project_id'=>$projectId,'user_id'=>$actorId,'role_code'=>'project_manager']);$this->db->commit();}catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw$e;}
    }

    public function update(int $assignmentId,string $roleCode,?string $effectiveFrom,?string $effectiveUntil,string $reason,int $expectedVersion,int $actorId): array
    {
        $this->db->beginTransaction();
        try{
            $before=$this->assignmentForUpdate($assignmentId);if(!$before||!(int)$before['active'])throw new AssignmentNotFound('Assignment not found.');
            $projectId=(int)$before['project_id'];$userId=(int)$before['user_id'];$this->authorization->require($projectId,'assignment.manage');
            if((int)$before['version']!==$expectedVersion)throw new StaleAssignment('The assignment was changed by another request.');
            $this->validateCommon($projectId,$userId,$roleCode,$effectiveFrom,$effectiveUntil,$reason,$actorId);
            $role=$this->role($roleCode);$from=$this->dateTime($effectiveFrom)??$before['effective_from'];$until=$this->dateTime($effectiveUntil);
            $this->guardLastManager($projectId,$assignmentId,(string)$before['role_code'],$roleCode);
            $s=$this->db->prepare("UPDATE project_role_assignments SET role_id=?,effective_from=?,effective_until=?,reason=?,version=version+1,updated_at=UTC_TIMESTAMP(6) WHERE id=? AND version=? AND active=1");
            $s->execute([$role['id'],$from,$until,$reason,$assignmentId,$expectedVersion]);if($s->rowCount()!==1)throw new StaleAssignment('The assignment was changed by another request.');
            $after=$this->assignmentById($assignmentId);$this->syncLegacy($projectId,$userId,$role['name'],$this->roleCanEdit((int)$role['id']),$actorId);
            $this->invalidateUser($userId);$this->audit('assignment.role_changed',$projectId,$assignmentId,$actorId,$before,$after,$reason);$this->outbox('assignment.role_changed',$assignmentId,['project_id'=>$projectId,'user_id'=>$userId,'role_code'=>$roleCode]);
            $this->db->commit();return$after;
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw$e;}
    }

    public function revoke(int $assignmentId,string $reason,int $expectedVersion,int $actorId): void
    {
        $reason=$this->reason($reason);$this->db->beginTransaction();
        try{
            $before=$this->assignmentForUpdate($assignmentId);if(!$before||!(int)$before['active'])throw new AssignmentNotFound('Assignment not found.');
            $projectId=(int)$before['project_id'];$userId=(int)$before['user_id'];$this->authorization->require($projectId,'assignment.manage');
            if((int)$before['version']!==$expectedVersion)throw new StaleAssignment('The assignment was changed by another request.');
            $this->guardLastManager($projectId,$assignmentId,(string)$before['role_code'],'revoked');
            $s=$this->db->prepare("UPDATE project_role_assignments SET active=0,revoked_by=?,revoked_at=UTC_TIMESTAMP(6),revoked_reason=?,version=version+1,updated_at=UTC_TIMESTAMP(6) WHERE id=? AND version=? AND active=1");
            $s->execute([$actorId,$reason,$assignmentId,$expectedVersion]);if($s->rowCount()!==1)throw new StaleAssignment('The assignment was changed by another request.');
            $this->db->prepare("DELETE FROM project_members WHERE project_id=? AND user_id=?")->execute([$projectId,$userId]);$this->invalidateUser($userId);
            $after=$this->assignmentById($assignmentId);$this->audit('assignment.revoked',$projectId,$assignmentId,$actorId,$before,$after,$reason);$this->outbox('assignment.revoked',$assignmentId,['project_id'=>$projectId,'user_id'=>$userId]);
            $this->db->commit();
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw$e;}
    }

    private function validateCommon(int $projectId,int $userId,string $roleCode,?string $from,?string $until,string $reason,int $actorId): void
    {
        if(!$this->authorization->schemaReady())throw new ValidationException('Import database_upgrade.sql before changing project assignments.');
        $this->reason($reason);$this->role($roleCode);
        $s=$this->db->prepare("SELECT COUNT(*) FROM projects WHERE id=?");$s->execute([$projectId]);if(!(int)$s->fetchColumn())throw new ValidationException('Select a valid project.');
        $s=$this->db->prepare("SELECT COUNT(*) FROM users WHERE id=? AND status='active'");$s->execute([$userId]);if(!(int)$s->fetchColumn())throw new ValidationException('Select an active user.');
        if($from!==null&&$from!==''&&!$this->dateTime($from))throw new ValidationException('Effective From is invalid.');
        if($until!==null&&$until!==''&&!$this->dateTime($until))throw new ValidationException('Effective Until is invalid.');
        $normalizedFrom=$this->dateTime($from)??gmdate('Y-m-d H:i:s');$normalizedUntil=$this->dateTime($until);
        if($normalizedUntil!==null&&$normalizedUntil<=$normalizedFrom)throw new ValidationException('Effective Until must be later than Effective From.');
        if($roleCode==='project_manager'&&($_SESSION['role']??'')!=='admin')throw new AssignmentForbidden('Only a System Administrator may grant the Project Manager role.');
        if($userId===$actorId&&$roleCode==='project_manager'&&($_SESSION['role']??'')!=='admin')throw new AssignmentForbidden('Self-elevation is not allowed.');
    }

    private function reason(string $reason): string
    {
        $reason=trim(preg_replace('/\s+/u',' ',$reason)??'');$length=function_exists('mb_strlen')?mb_strlen($reason):strlen($reason);
        if($length<10||$length>500)throw new ValidationException('Reason must contain 10 to 500 characters.');return$reason;
    }

    private function role(string $code): array
    {
        $s=$this->db->prepare("SELECT id,code,name FROM roles WHERE code=? AND scope='project' AND active=1 LIMIT 1");$s->execute([$code]);$role=$s->fetch();if(!$role)throw new ValidationException('Select a valid active project role.');return$role;
    }

    private function roleCanEdit(int $roleId): bool
    {
        $s=$this->db->prepare("SELECT COUNT(*) FROM role_permissions rp JOIN permissions p ON p.id=rp.permission_id WHERE rp.role_id=? AND p.code IN ('boq.edit','progress.update','submittal.create_edit','procurement.create_edit','workplan.create_edit')");$s->execute([$roleId]);return(bool)$s->fetchColumn();
    }

    private function dateTime(?string $value): ?string
    {
        if($value===null||trim($value)==='')return null;try{$date=new \DateTimeImmutable($value,new \DateTimeZone('UTC'));return$date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');}catch(\Throwable){return null;}
    }

    private function activeForUpdate(int $projectId,int $userId): ?array{$s=$this->db->prepare("SELECT * FROM project_role_assignments WHERE project_id=? AND user_id=? AND active=1 FOR UPDATE");$s->execute([$projectId,$userId]);return$s->fetch()?:null;}
    private function assignmentForUpdate(int $id): ?array{$s=$this->db->prepare("SELECT a.*,r.code AS role_code,r.name AS role_name FROM project_role_assignments a JOIN roles r ON r.id=a.role_id WHERE a.id=? FOR UPDATE");$s->execute([$id]);return$s->fetch()?:null;}
    private function assignmentById(int $id): array{$s=$this->db->prepare("SELECT a.*,r.code AS role_code,r.name AS role_name,u.name AS user_name,u.email FROM project_role_assignments a JOIN roles r ON r.id=a.role_id JOIN users u ON u.id=a.user_id WHERE a.id=?");$s->execute([$id]);return$s->fetch()?:[];}

    private function guardLastManager(int $projectId,int $assignmentId,string $oldRole,string $newRole): void
    {
        if($oldRole!=='project_manager'||$newRole==='project_manager')return;
        $s=$this->db->prepare("SELECT COUNT(*) FROM project_role_assignments a JOIN roles r ON r.id=a.role_id WHERE a.project_id=? AND a.active=1 AND a.id<>? AND r.code='project_manager'");$s->execute([$projectId,$assignmentId]);
        if(!(int)$s->fetchColumn())throw new AssignmentConflict('Assign another Project Manager before removing the final Project Manager.');
    }

    private function syncLegacy(int $projectId,int $userId,string $roleName,bool $canEdit,int $actorId): void
    {
        $s=$this->db->prepare("INSERT INTO project_members(project_id,user_id,project_role,can_edit,assigned_by,assigned_at) VALUES(?,?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE project_role=VALUES(project_role),can_edit=VALUES(can_edit),assigned_by=VALUES(assigned_by),assigned_at=VALUES(assigned_at)");$s->execute([$projectId,$userId,$roleName,$canEdit?1:0,$actorId]);
    }
    private function invalidateUser(int $userId): void{$this->db->prepare("UPDATE users SET auth_version=auth_version+1,updated_at=UTC_TIMESTAMP() WHERE id=?")->execute([$userId]);}

    private function audit(string $eventType,int $projectId,int $assignmentId,int $actorId,?array $before,array $after,string $reason): void
    {
        $previous=$this->db->prepare("SELECT HEX(event_hash) FROM audit_logs WHERE project_id=? AND event_hash IS NOT NULL ORDER BY id DESC LIMIT 1 FOR UPDATE");$previous->execute([$projectId]);$previousHash=(string)($previous->fetchColumn()?:'');
        $eventUuid=request_id();$requestId=request_id();$beforeJson=$before?json_encode($before,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE):null;$afterJson=json_encode($after,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $canonical=json_encode([$eventUuid,$requestId,$eventType,$actorId,$projectId,$assignmentId,$beforeJson,$afterJson,$reason,gmdate('Y-m-d H:i')],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);$hash=hash('sha256',$previousHash.$canonical);
        $s=$this->db->prepare("INSERT INTO audit_logs(event_uuid,request_id,event_type,user_id,project_id,action,module,record_id,description,before_state,after_state,reason,ip_address,user_agent,previous_hash,event_hash) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,IF(?='',NULL,UNHEX(?)),UNHEX(?))");
        $ip=substr((string)($_SERVER['REMOTE_ADDR']??''),0,45);$ua=substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,255);
        $s->execute([$eventUuid,$requestId,$eventType,$actorId,$projectId,$eventType,'project_assignments',$assignmentId,substr($reason,0,500),$beforeJson,$afterJson,$reason,$ip,$ua,$previousHash,$previousHash,$hash]);
    }
    private function outbox(string $eventType,int $assignmentId,array $payload): void{$s=$this->db->prepare("INSERT INTO rbac_outbox(event_uuid,event_type,aggregate_id,payload) VALUES(?,?,?,?)");$s->execute([request_id(),$eventType,$assignmentId,json_encode($payload,JSON_UNESCAPED_SLASHES)]);}
}

class ValidationException extends RuntimeException {}
class AssignmentConflict extends RuntimeException {}
class StaleAssignment extends RuntimeException {}
class AssignmentNotFound extends RuntimeException {}
class AssignmentForbidden extends RuntimeException {}
