<?php
use App\Controllers\ApiController;use App\Controllers\ProjectAssignmentApiController;use App\Middleware\AuthMiddleware;use App\Middleware\CsrfMiddleware;
$router->get('api/boq',[ApiController::class,'boq'],[AuthMiddleware::class]);$router->get('api/mas-by-boq',[ApiController::class,'masByBoq'],[AuthMiddleware::class]);$router->get('api/manufacturers',[ApiController::class,'manufacturers'],[AuthMiddleware::class]);$router->get('api/progress',[ApiController::class,'progress'],[AuthMiddleware::class]);$router->get('api/submittals',[ApiController::class,'submittals'],[AuthMiddleware::class]);
$apiRead=[AuthMiddleware::class];$apiWrite=[AuthMiddleware::class,CsrfMiddleware::class];
$router->post('api/v1/projects/{id}/assignments',[ProjectAssignmentApiController::class,'create'],$apiWrite);
$router->put('api/v1/assignments/{id}',[ProjectAssignmentApiController::class,'update'],$apiWrite);
$router->delete('api/v1/assignments/{id}',[ProjectAssignmentApiController::class,'remove'],$apiWrite);
$router->get('api/v1/users/{id}/projects',[ProjectAssignmentApiController::class,'userProjects'],$apiRead);
$router->get('api/v1/projects/{id}/assignments',[ProjectAssignmentApiController::class,'projectAssignments'],$apiRead);

