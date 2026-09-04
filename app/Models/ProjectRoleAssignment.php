<?php
namespace App\Models;

use PDO;

final class ProjectRoleAssignment
{
    public function __construct(private PDO $db) {}

    public function forProject(int $projectId,bool $history=false): array
    {
        $where=$history?'a.project_id=?':'a.project_id=? AND a.active=1';
        $s=$this->db->prepare("SELECT a.*,u.name,u.email,r.code AS role_code,r.name AS role_name,assigner.name AS assigned_by_name,revoker.name AS revoked_by_name FROM project_role_assignments a JOIN users u ON u.id=a.user_id JOIN roles r ON r.id=a.role_id LEFT JOIN users assigner ON assigner.id=a.assigned_by LEFT JOIN users revoker ON revoker.id=a.revoked_by WHERE {$where} ORDER BY a.active DESC,u.name,a.id DESC");
        $s->execute([$projectId]);return$s->fetchAll();
    }

    public function roles(): array
    {
        return$this->db->query("SELECT id,code,name,description FROM roles WHERE scope='project' AND active=1 ORDER BY CASE code WHEN 'project_manager' THEN 1 WHEN 'project_engineer' THEN 2 WHEN 'mep_engineer' THEN 3 WHEN 'coordinator' THEN 4 ELSE 5 END,name")->fetchAll();
    }

    public function rolePermissions(): array
    {
        $rows=$this->db->query("SELECT r.code,p.code AS permission_code FROM roles r JOIN role_permissions rp ON rp.role_id=r.id JOIN permissions p ON p.id=rp.permission_id WHERE r.active=1 AND p.active=1 ORDER BY r.code,p.code")->fetchAll();$map=[];
        foreach($rows as $row)$map[$row['code']][]=$row['permission_code'];return$map;
    }

    public function active(int $projectId,int $userId): ?array
    {
        $s=$this->db->prepare("SELECT a.*,r.code AS role_code,r.name AS role_name FROM project_role_assignments a JOIN roles r ON r.id=a.role_id WHERE a.project_id=? AND a.user_id=? AND a.active=1 LIMIT 1");$s->execute([$projectId,$userId]);return$s->fetch()?:null;
    }
}

