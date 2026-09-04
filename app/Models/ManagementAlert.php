<?php
namespace App\Models;
use PDO;

final class ManagementAlert
{
    public function __construct(private PDO $db){}

    public function active(string $scope,int $limit=100):array
    {
        $limit=max(1,min(200,$limit));
        $sql="SELECT alerts.* FROM (
            SELECT 'Overdue Activity' alert_type,p.id project_id,p.project_name,pp.task description,
                   pp.planned_end_date due_date,DATEDIFF(CURDATE(),pp.planned_end_date) days_value,
                   'High' severity,'Project Progress' source_module,pp.id source_id
            FROM project_progress pp JOIN projects p ON p.id=pp.project_id
            WHERE {$scope} AND pp.item_type='Measurable Item' AND pp.planned_end_date<CURDATE() AND pp.percentage_complete<100
            UNION ALL
            SELECT 'Progress Behind Plan',p.id,p.project_name,pp.task,pp.planned_end_date,
                   ROUND(pp.planned_percentage-pp.percentage_complete,1),'High','Project Progress',pp.id
            FROM project_progress pp JOIN projects p ON p.id=pp.project_id
            WHERE {$scope} AND pp.item_type='Measurable Item' AND pp.planned_percentage-pp.percentage_complete>=10
            UNION ALL
            SELECT 'Near Due Activity',p.id,p.project_name,pp.task,pp.planned_end_date,
                   DATEDIFF(pp.planned_end_date,CURDATE()),'Medium','Project Progress',pp.id
            FROM project_progress pp JOIN projects p ON p.id=pp.project_id
            WHERE {$scope} AND pp.item_type='Measurable Item' AND pp.percentage_complete<100
              AND pp.planned_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)
            UNION ALL
            SELECT 'Missing Activity Date',p.id,p.project_name,pp.task,NULL,0,'Medium','Project Progress',pp.id
            FROM project_progress pp JOIN projects p ON p.id=pp.project_id
            WHERE {$scope} AND pp.item_type='Measurable Item' AND pp.planned_end_date IS NULL
              AND pp.status NOT IN('Complete','Completed','Not Applicable')
            UNION ALL
            SELECT CASE pr.status
                       WHEN 'Not Started' THEN 'Procurement Pending'
                       WHEN 'Purchase Order (PO) Issued' THEN 'PO Issued / Delivery Pending'
                       ELSE 'Delivery Expected'
                   END,
                   p.id,p.project_name,pr.material_description,pr.expected_delivery_date,
                   CASE WHEN pr.expected_delivery_date IS NULL THEN 0 ELSE DATEDIFF(CURDATE(),pr.expected_delivery_date) END,
                   CASE WHEN pr.expected_delivery_date<CURDATE() THEN 'Critical' WHEN pr.status='Not Started' THEN 'High' ELSE 'Medium' END,
                   'Procurement',pr.id
            FROM procurement pr JOIN projects p ON p.id=pr.project_id
            WHERE {$scope} AND pr.status<>'Good Received / Delivered'
            UNION ALL
            SELECT CASE WHEN s.status IN('C','D') THEN 'MAS Requires Revision' ELSE 'MAS Aging' END,
                   p.id,p.project_name,s.material_description,
                   CASE WHEN s.submitted_date IS NULL THEN NULL ELSE DATE_ADD(s.submitted_date,INTERVAL 7 DAY) END,
                   CASE WHEN s.submitted_date IS NULL THEN 0 ELSE DATEDIFF(CURDATE(),s.submitted_date) END,
                   CASE WHEN s.status='D' THEN 'Critical' WHEN s.status='C' THEN 'High' ELSE 'Medium' END,
                   'Submittals',s.id
            FROM submittals s JOIN projects p ON p.id=s.project_id
            WHERE {$scope} AND s.status IN('UR','C','D')
            UNION ALL
            SELECT 'Overdue Work Plan',p.id,p.project_name,CONCAT(w.discipline,' - ',COALESCE(w.boq_no,'')),
                   w.planned_finish,DATEDIFF(CURDATE(),w.planned_finish),'High','Work Plan',w.id
            FROM workplan w JOIN projects p ON p.id=w.project_id
            WHERE {$scope} AND w.planned_finish<CURDATE() AND w.completion_percentage<100
        ) alerts
        ORDER BY CASE alerts.severity WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 ELSE 4 END,
                 alerts.days_value DESC,alerts.project_name,alerts.alert_type
        LIMIT {$limit}";
        return $this->db->query($sql)->fetchAll();
    }
}
