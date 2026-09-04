<?php
use App\Controllers\AuditController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ProcurementController;
use App\Controllers\ProgressController;
use App\Controllers\ProjectController;
use App\Controllers\ProjectMemberController;
use App\Controllers\ReportController;
use App\Controllers\SubmittalController;
use App\Controllers\SupplierController;
use App\Controllers\UserController;
use App\Controllers\WorkplanController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

$auth=[AuthMiddleware::class];
$write=[AuthMiddleware::class,CsrfMiddleware::class];

$router->get('login',[AuthController::class,'login']);
$router->post('login',[AuthController::class,'login']);
$router->get('signup',[AuthController::class,'signup']);
$router->post('signup',[AuthController::class,'signup'],[CsrfMiddleware::class]);
$router->get('setup-admin',[AuthController::class,'setup']);
$router->post('setup-admin',[AuthController::class,'setup'],[CsrfMiddleware::class]);
$router->get('reset-password',[AuthController::class,'resetPassword']);
$router->post('reset-password',[AuthController::class,'resetPassword'],[CsrfMiddleware::class]);
$router->post('logout',[AuthController::class,'logout'],$write);

$router->get('dashboard',[DashboardController::class,'index'],$auth);
$router->get('projects',[ProjectController::class,'index'],$auth);
$router->post('projects',[ProjectController::class,'index'],$write);
$router->get('select-project',[ProjectController::class,'select'],$auth);
$router->get('progress',[ProgressController::class,'index'],$auth);
$router->post('progress',[ProgressController::class,'index'],$write);
$router->get('submittals',[SubmittalController::class,'index'],$auth);
$router->post('submittals',[SubmittalController::class,'index'],$write);
$router->get('procurement',[ProcurementController::class,'index'],$auth);
$router->post('procurement',[ProcurementController::class,'index'],$write);
$router->get('workplan',[WorkplanController::class,'index'],$auth);
$router->post('workplan',[WorkplanController::class,'index'],$write);
$router->get('suppliers',[SupplierController::class,'index'],$auth);
$router->post('suppliers',[SupplierController::class,'index'],$write);
$router->get('users',[UserController::class,'index'],$auth);
$router->post('users',[UserController::class,'index'],$write);

$router->get('project-members',[ProjectMemberController::class,'index'],$auth);
$router->post('project-members-save',[ProjectMemberController::class,'save'],$write);
$router->post('project-members-remove',[ProjectMemberController::class,'remove'],$write);
$router->get('audit',[AuditController::class,'index'],$auth);
$router->get('reports/progress-pdf',[ReportController::class,'progressPdf'],$auth);
$router->get('reports/workplan-pdf',[ReportController::class,'workplanPdf'],$auth);
$router->get('exports/progress-xlsx',[ReportController::class,'progressXlsx'],$auth);
$router->get('exports/management-xlsx',[ReportController::class,'managementXlsx'],$auth);
