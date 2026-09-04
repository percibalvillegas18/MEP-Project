<?php
namespace App\Models;

final class Project
{
    public function __construct(private \PDO $db) {}

    public function find(int $projectId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM projects WHERE id=?');
        $stmt->execute([$projectId]);
        $project = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $project ?: null;
    }

    public function usersNotAssigned(int $projectId,bool $rbac=false): array
    {
        $table=$rbac?'project_role_assignments':'project_members';$active=$rbac?' AND active=1':'';
        $stmt = $this->db->prepare(
            'SELECT u.id,u.name,u.email FROM users u
             WHERE u.status=? AND NOT EXISTS
             (SELECT 1 FROM '.$table.' pm WHERE pm.project_id=? AND pm.user_id=u.id'.$active.')
             ORDER BY u.name'
        );
        $stmt->execute(['active', $projectId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
