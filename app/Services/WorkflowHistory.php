<?php
namespace App\Services;
use PDO;
final class WorkflowHistory {
    public function __construct(private PDO $db) {}
    public function record(string $entity,int $id,int $projectId,string $type,?string $old,?string $new,int $userId,string $remarks=''): void {
        if ($old===$new) return;
        $s=$this->db->prepare("INSERT INTO workflow_status_history(entity_type,entity_id,project_id,status_type,old_status,new_status,remarks,changed_by) VALUES(?,?,?,?,?,?,?,?)");
        $s->execute([$entity,$id,$projectId,$type,$old,$new,$remarks,$userId]);
    }
}
