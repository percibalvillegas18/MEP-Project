<?php
namespace App\Models;
use PDO;
final class AuditLog {
 public function __construct(private PDO $db){}
 public function search(string $scope,bool $includeGlobal,string $q,string $module,string $from,string $to): array {
  $visibility=$includeGlobal?"(a.project_id IS NULL OR {$scope})":"(a.project_id IS NOT NULL AND {$scope})";
  $sql="SELECT a.*,u.name,p.project_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id LEFT JOIN projects p ON p.id=a.project_id WHERE {$visibility}";$args=[];
  if($q!==''){$sql.=" AND (a.description LIKE ? OR u.name LIKE ? OR a.action LIKE ?)";$like="%$q%";$args=[$like,$like,$like];}
  if($module!==''){$sql.=" AND a.module=?";$args[]=$module;} if($from!==''){$sql.=" AND a.created_at>=?";$args[]=$from.' 00:00:00';} if($to!==''){$sql.=" AND a.created_at<=?";$args[]=$to.' 23:59:59';}
  $sql.=" ORDER BY a.id DESC LIMIT 500";$s=$this->db->prepare($sql);$s->execute($args);return $s->fetchAll();
 }
}
