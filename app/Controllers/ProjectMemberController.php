<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectRoleAssignment;
use App\Services\ProjectAuthorization;
use App\Services\ProjectRoleAssignmentService;
use App\Services\AssignmentConflict;
use App\Services\AssignmentForbidden;
use App\Services\AssignmentNotFound;
use App\Services\StaleAssignment;
use App\Services\ValidationException;

final class ProjectMemberController extends Controller
{
    private \PDO $db;private Project $projects;private ProjectAuthorization $authorization;
    public function __construct(){global$pdo;$this->db=$pdo;$this->projects=new Project($pdo);$this->authorization=new ProjectAuthorization($pdo);}
    public function index(): void
    {
        $id=(int)($_GET['project_id']??0);$project=$this->projects->find($id);if(!$project){http_response_code(404);exit('Project not found.');}
        $ready=$this->authorization->schemaReady();if($ready)$this->authorization->require($id,'assignment.manage');else\require_role('admin','project_manager');$history=($_GET['history']??'')==='1';
        if($ready){$model=new ProjectRoleAssignment($this->db);$members=$model->forProject($id,$history);$roles=$model->roles();$rolePermissions=$model->rolePermissions();$available=$this->projects->usersNotAssigned($id,true);}
        else{$members=(new ProjectMember($this->db))->forProject($id);$roles=[];$rolePermissions=[];$available=[];}
        $this->view('project_members/index',['project'=>$project,'members'=>$members,'available'=>$available,'roles'=>$roles,'rolePermissions'=>$rolePermissions,'rbacReady'=>$ready,'history'=>$history]);
    }
    public function save(): void
    {
        \verify_csrf();$projectId=(int)($_POST['project_id']??0);if(!$this->authorization->schemaReady()){\flash('error','Import database_upgrade.sql before changing assignments.');\redirect('mvc.php?route=project-members&project_id='.$projectId);}
        $service=new ProjectRoleAssignmentService($this->db,$this->authorization);$assignmentId=(int)($_POST['assignment_id']??0);$reason=(string)($_POST['reason']??'');$role=(string)($_POST['role_code']??'');$from=(string)($_POST['effective_from']??'');$until=(string)($_POST['effective_until']??'');
        try{if($assignmentId){$service->update($assignmentId,$role,$from,$until,$reason,(int)($_POST['version']??0),(int)$_SESSION['user_id']);\flash('success','Project role updated and access cache invalidated.');}else{$service->create($projectId,(int)($_POST['user_id']??0),$role,$from,$until,$reason,(int)$_SESSION['user_id']);\flash('success','Project role assigned successfully.');}}
        catch(ValidationException|AssignmentConflict|AssignmentForbidden|AssignmentNotFound|StaleAssignment $e){\flash('error',$e->getMessage());}
        catch(\Throwable $e){error_log('Project assignment save failed: '.$e->getMessage());\flash('error',defined('APP_DEBUG')&&APP_DEBUG?$e->getMessage():'The assignment could not be saved. Check the server log with the request ID.');}\redirect('mvc.php?route=project-members&project_id='.$projectId);
    }
    public function remove(): void
    {
        \verify_csrf();$projectId=(int)($_POST['project_id']??0);try{(new ProjectRoleAssignmentService($this->db,$this->authorization))->revoke((int)($_POST['assignment_id']??0),(string)($_POST['reason']??''),(int)($_POST['version']??0),(int)$_SESSION['user_id']);\flash('success','Project assignment revoked. The change is retained in assignment history.');}
        catch(ValidationException|AssignmentConflict|AssignmentForbidden|AssignmentNotFound|StaleAssignment $e){\flash('error',$e->getMessage());}
        catch(\Throwable $e){error_log('Project assignment revoke failed: '.$e->getMessage());\flash('error',defined('APP_DEBUG')&&APP_DEBUG?$e->getMessage():'The assignment could not be revoked. Check the server log with the request ID.');}\redirect('mvc.php?route=project-members&project_id='.$projectId);
    }
}
