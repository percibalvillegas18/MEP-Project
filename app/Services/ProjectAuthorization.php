<?php
namespace App\Services;

use PDO;

final class ProjectAuthorization
{
    private static array $requestCache=[];
    private ?bool $ready=null;

    public function __construct(private PDO $db) {}

    public function schemaReady(): bool
    {
        if($this->ready!==null)return $this->ready;
        try{
            $s=$this->db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('roles','permissions','role_permissions','project_role_assignments')");
            return $this->ready=((int)$s->fetchColumn()===4);
        }catch(\Throwable){return $this->ready=false;}
    }

    public function can(int $userId,int $projectId,string $permission): bool
    {
        if($userId<1||$projectId<1)return false;
        $systemRole=(string)($_SESSION['role']??'user');
        if($systemRole==='admin')return true;
        $key=$userId.':'.$projectId.':'.$permission.':'.(int)($_SESSION['auth_version']??0);
        if(array_key_exists($key,self::$requestCache))return self::$requestCache[$key];
        if(!$this->schemaReady())return self::$requestCache[$key]=$this->legacyCan($userId,$projectId,$permission,$systemRole);
        $sql="SELECT 1 FROM project_role_assignments a JOIN roles r ON r.id=a.role_id AND r.active=1 JOIN role_permissions rp ON rp.role_id=r.id JOIN permissions p ON p.id=rp.permission_id AND p.active=1 WHERE a.user_id=? AND a.project_id=? AND a.active=1 AND a.effective_from<=UTC_TIMESTAMP(6) AND (a.effective_until IS NULL OR a.effective_until>UTC_TIMESTAMP(6)) AND p.code=? LIMIT 1";
        $s=$this->db->prepare($sql);$s->execute([$userId,$projectId,$permission]);
        return self::$requestCache[$key]=(bool)$s->fetchColumn();
    }

    public function canAny(string $permission): bool
    {
        $userId=(int)($_SESSION['user_id']??0);if($userId<1)return false;
        if(($_SESSION['role']??'')==='admin')return true;
        if(!$this->schemaReady())return in_array($_SESSION['role']??'',['project_manager','project_engineer','mep_engineer'],true);
        $s=$this->db->prepare("SELECT 1 FROM project_role_assignments a JOIN roles r ON r.id=a.role_id AND r.active=1 JOIN role_permissions rp ON rp.role_id=r.id JOIN permissions p ON p.id=rp.permission_id AND p.active=1 WHERE a.user_id=? AND a.active=1 AND a.effective_from<=UTC_TIMESTAMP(6) AND (a.effective_until IS NULL OR a.effective_until>UTC_TIMESTAMP(6)) AND p.code=? LIMIT 1");
        $s->execute([$userId,$permission]);return(bool)$s->fetchColumn();
    }

    public function scopeClause(string $projectColumn='p.id'): string
    {
        if(($_SESSION['role']??'')==='admin')return'1=1';
        $uid=(int)($_SESSION['user_id']??0);
        if(!$this->schemaReady()){
            if(($_SESSION['role']??'')==='project_manager')return'1=1';
            return"EXISTS (SELECT 1 FROM project_members pm_scope WHERE pm_scope.project_id={$projectColumn} AND pm_scope.user_id={$uid})";
        }
        return"EXISTS (SELECT 1 FROM project_role_assignments pra_scope WHERE pra_scope.project_id={$projectColumn} AND pra_scope.user_id={$uid} AND pra_scope.active=1 AND pra_scope.effective_from<=UTC_TIMESTAMP(6) AND (pra_scope.effective_until IS NULL OR pra_scope.effective_until>UTC_TIMESTAMP(6)))";
    }

    public function require(int $projectId,string $permission): void
    {
        if(!$this->can((int)($_SESSION['user_id']??0),$projectId,$permission)){
            http_response_code(403);exit('Access denied. You do not have permission for this project.');
        }
    }

    public function projectsForUser(int $targetUserId): array
    {
        if(!$this->schemaReady()){
            $s=$this->db->prepare("SELECT p.id,p.project_name,p.status,pm.project_role AS role_name,pm.assigned_at,NULL AS effective_until FROM project_members pm JOIN projects p ON p.id=pm.project_id WHERE pm.user_id=? ORDER BY p.project_name");
            $s->execute([$targetUserId]);return$s->fetchAll();
        }
        $s=$this->db->prepare("SELECT p.id,p.project_name,p.status,r.name AS role_name,a.assigned_at,a.effective_until FROM project_role_assignments a JOIN projects p ON p.id=a.project_id JOIN roles r ON r.id=a.role_id WHERE a.user_id=? AND a.active=1 AND a.effective_from<=UTC_TIMESTAMP(6) AND (a.effective_until IS NULL OR a.effective_until>UTC_TIMESTAMP(6)) ORDER BY p.project_name");
        $s->execute([$targetUserId]);return$s->fetchAll();
    }

    private function legacyCan(int $userId,int $projectId,string $permission,string $systemRole): bool
    {
        if($permission==='assignment.manage'&&$systemRole==='project_manager')return true;
        $edit=!in_array($permission,['project.view','dashboard.view','boq.view','submittal.view','procurement.view','workplan.view','report.export'],true);
        $s=$this->db->prepare('SELECT can_edit FROM project_members WHERE project_id=? AND user_id=? LIMIT 1');$s->execute([$projectId,$userId]);$row=$s->fetch();
        return(bool)$row&&(!$edit||(bool)$row['can_edit']);
    }
}

