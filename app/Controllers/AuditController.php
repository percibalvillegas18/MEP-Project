<?php
namespace App\Controllers;
use App\Core\Controller;use App\Models\AuditLog;
final class AuditController extends Controller {
 public function index():void { \require_role('admin','project_manager');global $pdo;$q=trim($_GET['q']??'');$module=trim($_GET['module']??'');$from=trim($_GET['from']??'');$to=trim($_GET['to']??'');$rows=(new AuditLog($pdo))->search(\project_scope_clause('p.id'),\has_role('admin'),$q,$module,$from,$to);$this->view('audit/index',compact('rows','q','module','from','to')); }
}
