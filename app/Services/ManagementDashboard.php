<?php
namespace App\Services;
use PDO;
use App\Models\ManagementAlert;
final class ManagementDashboard {
 public function __construct(private PDO $db,private string $scope){}
 public function data(): array {
  $stats=[];
  $stats['projects']=(int)$this->db->query("SELECT COUNT(*) FROM projects p WHERE {$this->scope}")->fetchColumn();
  $stats['overdue']=(int)$this->db->query("SELECT COUNT(*) FROM project_progress pp JOIN projects p ON p.id=pp.project_id WHERE {$this->scope} AND pp.item_type='Measurable Item' AND pp.planned_end_date<CURDATE() AND pp.percentage_complete<100")->fetchColumn();
  $stats['procurement_open']=(int)$this->db->query("SELECT COUNT(*) FROM procurement pr JOIN projects p ON p.id=pr.project_id WHERE {$this->scope} AND pr.status<>'Good Received / Delivered'")->fetchColumn();
  $stats['mas_action']=(int)$this->db->query("SELECT COUNT(*) FROM submittals s JOIN projects p ON p.id=s.project_id WHERE {$this->scope} AND s.status IN('UR','C','D')")->fetchColumn();
  $stats['near_due']=(int)$this->db->query("SELECT COUNT(*) FROM project_progress pp JOIN projects p ON p.id=pp.project_id WHERE {$this->scope} AND pp.item_type='Measurable Item' AND pp.percentage_complete<100 AND pp.planned_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)")->fetchColumn();
  $stats['missing_dates']=(int)$this->db->query("SELECT COUNT(*) FROM project_progress pp JOIN projects p ON p.id=pp.project_id WHERE {$this->scope} AND pp.item_type='Measurable Item' AND pp.planned_end_date IS NULL")->fetchColumn();
  $portfolio=$this->db->query("SELECT CASE WHEN COALESCE(SUM(pp.activity_weight),0)<=0 THEN 0.00 WHEN SUM(pp.activity_weight*pp.percentage_complete)>=SUM(pp.activity_weight)*100 THEN 100.00 ELSE LEAST(99.99,ROUND(SUM(pp.activity_weight*pp.percentage_complete)/SUM(pp.activity_weight),2)) END actual,CASE WHEN COALESCE(SUM(pp.activity_weight),0)<=0 THEN 0.00 WHEN SUM(pp.activity_weight*pp.planned_percentage)>=SUM(pp.activity_weight)*100 THEN 100.00 ELSE LEAST(99.99,ROUND(SUM(pp.activity_weight*pp.planned_percentage)/SUM(pp.activity_weight),2)) END planned FROM project_progress pp JOIN projects p ON p.id=pp.project_id WHERE {$this->scope} AND pp.item_type='Measurable Item'")->fetch()?:['actual'=>0.00,'planned'=>0.00];
  $alerts=(new ManagementAlert($this->db))->active($this->scope,100);
  $projects=$this->db->query("SELECT p.id,p.project_name,CASE WHEN COALESCE(SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END),0)<=0 THEN 0.00 WHEN SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight*pp.percentage_complete ELSE 0 END)>=SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END)*100 THEN 100.00 ELSE LEAST(99.99,ROUND(SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight*pp.percentage_complete ELSE 0 END)/SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END),2)) END actual,CASE WHEN COALESCE(SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END),0)<=0 THEN 0.00 WHEN SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight*pp.planned_percentage ELSE 0 END)>=SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END)*100 THEN 100.00 ELSE LEAST(99.99,ROUND(SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight*pp.planned_percentage ELSE 0 END)/SUM(CASE WHEN pp.item_type='Measurable Item' THEN pp.activity_weight ELSE 0 END),2)) END planned FROM projects p LEFT JOIN project_progress pp ON pp.project_id=p.id WHERE {$this->scope} GROUP BY p.id ORDER BY p.project_name")->fetchAll();
  return compact('stats','portfolio','alerts','projects');
 }
}
