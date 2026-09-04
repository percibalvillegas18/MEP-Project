<?php
 require_login();
use App\Services\ManagementDashboard;
$data=(new ManagementDashboard($pdo,project_scope_clause('p.id')))->data(); extract($data);
$actual=(float)$portfolio['actual'];$planned=(float)$portfolio['planned'];$variance=round($actual-$planned,1);
$pageTitle='Management Dashboard';
