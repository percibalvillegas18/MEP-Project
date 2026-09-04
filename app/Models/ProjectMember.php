<?php
namespace App\Models;
use PDO;
final class ProjectMember {
    public function __construct(private PDO $db) {}
    public function forProject(int $projectId): array {
        $s=$this->db->prepare("SELECT pm.*,u.name,u.email FROM project_members pm JOIN users u ON u.id=pm.user_id WHERE pm.project_id=? ORDER BY u.name"); $s->execute([$projectId]); return $s->fetchAll();
    }
    public function assign(int $projectId,int $userId,string $role,bool $canEdit,int $by): void {
        $s=$this->db->prepare("INSERT INTO project_members(project_id,user_id,project_role,can_edit,assigned_by) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE project_role=VALUES(project_role),can_edit=VALUES(can_edit),assigned_by=VALUES(assigned_by),assigned_at=NOW()");
        $s->execute([$projectId,$userId,$role,$canEdit?1:0,$by]);
    }
    public function remove(int $projectId,int $userId): void { $s=$this->db->prepare("DELETE FROM project_members WHERE project_id=? AND user_id=?"); $s->execute([$projectId,$userId]); }
}
