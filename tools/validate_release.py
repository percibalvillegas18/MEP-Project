#!/usr/bin/env python3
"""Static release checks that do not require a database connection."""
from pathlib import Path
import re
import sys

ROOT=Path(__file__).resolve().parents[1]
errors=[]

def require(condition,message):
    if not condition: errors.append(message)

require((ROOT/'VERSION.txt').read_text().strip()=='007.4','VERSION.txt is not 007.4')
require(ROOT.name=='MEP_Projects_Version_007.4',f'Project directory is not Version 007.4: {ROOT.name}')
release_numbers=set()
release_pattern=re.compile(r'\bVersion[ _.-]+([0-9]{3})\b',re.I)
for path in ROOT.rglob('*'):
    if path.is_file() and path.suffix.lower() in {'.md','.txt','.sql','.php','.py'}:
        release_numbers.update(release_pattern.findall(path.read_text(errors='replace')))
    release_numbers.update(release_pattern.findall(path.name))
require('007.4' in (ROOT/'README.md').read_text(errors='replace'),'Current Version 007.4 reference is missing')
spec=(ROOT/'MEP_Project_Portal_Version_007.4.md').read_text(errors='replace')
require('Stage 3: Workflow and Calculation Accuracy' in spec,'Stage 3 requirements are missing from the release specification')
require('Stage 4: User Experience and Miscellaneous Fixes' in spec,'Stage 4 requirements are missing from the release specification')
config=(ROOT/'config.php').read_text(errors='replace')
require("define('APP_DEBUG'" in config,'APP_DEBUG configuration is missing')
require("$isLocalRequest?'development':'production'" in config and "['localhost','127.0.0.1','::1']" in config,'Localhost development detection or non-local production default is missing')
require("define('APP_VERSION'" in config and "VERSION.txt" in config,'Runtime version must be sourced from VERSION.txt')
require(not (ROOT/'app/Legacy').exists(),'app/Legacy must not exist')
require(not (ROOT/'app/Core/LegacyRunner.php').exists(),'LegacyRunner must not exist')
require(not (ROOT/'app/Controllers/PageController.php').exists(),'PageController must not exist')

for removed in ['project.php','favicon.png','docs','vendor']:
    require(not (ROOT/removed).exists(),f'Balanced cleanup target still exists: {removed}')
root_markdown={p.name for p in ROOT.glob('*.md')}
require(root_markdown=={'README.md','ARCHITECTURE.md','RELEASE_NOTES_007.4.md','VALIDATION_007.4.md','MEP_Project_Portal_Version_007.4.md','IMPLEMENTATION_STATUS_007.4.md'},f'Unexpected root Markdown files: {sorted(root_markdown)}')
for endpoint in ['ajax_boq_list.php','ajax_mas_by_boq.php','ajax_mfr_list.php','ajax_progress_list.php','ajax_submittal_list.php','mvc.php']:
    require((ROOT/endpoint).is_file(),f'Required endpoint removed during cleanup: {endpoint}')

controller_text='\n'.join(p.read_text(errors='replace') for p in (ROOT/'app/Controllers').glob('*.php'))
actions=set(re.findall(r"ActionRunner::(?:collect|execute)\('([^']+)'",controller_text))
allowed_text=(ROOT/'app/Core/ActionRunner.php').read_text()
allowed=set(re.findall(r"'([a-z_]+)'",allowed_text.split('];',1)[0]))
action_files={p.stem for p in (ROOT/'app/Actions').glob('*.php')}
require(actions<=action_files,f'Controller actions missing files: {sorted(actions-action_files)}')
require(actions<=allowed,f'Controller actions missing allow-list entries: {sorted(actions-allowed)}')
require(action_files==allowed,f'Action file/allow-list mismatch: files-only={sorted(action_files-allowed)}, allowed-only={sorted(allowed-action_files)}')

view_refs=set(re.findall(r"(?:->view|View::render)\('([^']+)'",controller_text))
for view in view_refs:
    require((ROOT/'app/Views'/(view+'.php')).is_file(),f'Missing view: {view}')

views='\n'.join(p.read_text(errors='replace') for p in (ROOT/'app/Views').rglob('*.php'))
require(not re.search(r'\$pdo|->prepare\s*\(|->query\s*\(',views),'Database access found in a view')
projects_view=(ROOT/'app/Views/projects/index.php').read_text(errors='replace')
require("can_project_permission(" not in projects_view,'Projects view must receive permission flags instead of calling the PDO authorization helper')
require("can_manage_assignments" in projects_view and "can_edit_project" in projects_view,'Projects view permission flags are missing')

progress_view=(ROOT/'app/Views/progress/index.php').read_text(errors='replace')
required_columns={'priority','boq','task','description','qty','unit','timeline','total-days','progress','status','notes','remarks','actions'}
present_columns=set(re.findall(r'data-col="([^"]+)"',progress_view))
require(required_columns<=present_columns,f'Project Progress GridView is missing columns: {sorted(required_columns-present_columns)}')
require("compact: ['description','qty','unit','timeline','total-days','remarks']" in progress_view,'Compact View column contract changed')
require("standard: ['notes','remarks']" in progress_view,'Standard View column contract changed')
require('task-description-part' in progress_view and 'font-style:italic' in progress_view,'Task description sections are not styled in italic')
require('<th data-col="description">Task Description</th>' in progress_view,'Task Description must remain one GridView column')
for forbidden_column in ['deliverables','specifications','references','requirements','acceptance']:
    require(f'<th data-col="{forbidden_column}">' not in progress_view,f'Task Description section incorrectly rendered as a separate column: {forbidden_column}')
for heading in ['Objective','Key Deliverables','Requirements/Specifications','Remarks/References','Requirements','Acceptance Criteria']:
    require(heading in progress_view,f'Task description GridView heading missing: {heading}')
progress_action=(ROOT/'app/Actions/project_progress.php').read_text(errors='replace')
require('function task_description_parts(' in progress_action,'Task description parser is missing')
require("descriptionRow['description_parts']" in progress_action,'Parsed task description parts are not assigned to GridView rows')
require('td:not([data-col="boq"]):not([data-col="task"]):not([data-col="actions"]){display:none}' not in progress_view,'Heading/Group rows still collapse table cells')
require('.item-type-heading td:not(.col-hidden),.item-type-group td:not(.col-hidden){display:table-cell}' in progress_view,'Heading/Group rows do not preserve grid columns')

procurement_action=(ROOT/'app/Actions/procurement.php').read_text(errors='replace')
post_start=procurement_action.find("if ($_SERVER['REQUEST_METHOD'] === 'POST')")
commit_pos=procurement_action.find('$pdo->commit();')
form_start=procurement_action.find('/* --- Form visibility',commit_pos)
require(post_start>=0 and commit_pos>post_start and form_start>commit_pos,'Procurement transaction flow is incomplete')
require("    }\n    }\n\n    $pdo->commit();" not in procurement_action,'Procurement POST block closes before commit')

database=(ROOT/'database.sql').read_text(errors='replace')
expected_tables={'users','disciplines','login_attempts','password_reset_tokens','schema_migrations','roles','permissions','role_permissions','projects','project_members','project_role_assignments','rbac_outbox','project_progress','submittals','procurement','suppliers','workflow_status_history','workplan','workplan_photos','file_cleanup_queue','audit_logs'}
created_tables=re.findall(r'CREATE TABLE `([^`]+)`',database,re.I)
require(set(created_tables)==expected_tables and len(created_tables)==len(expected_tables),f'Clean database table set is incorrect: {created_tables}')
require(database.count('CREATE DATABASE IF NOT EXISTS `mep_database`')==1,'database.sql must create mep_database exactly once')
require(set(re.findall(r'INSERT INTO `([^`]+)`',database,re.I))=={'disciplines','roles','permissions','role_permissions','schema_migrations'},'database.sql may seed only disciplines and RBAC metadata')
for forbidden in ['pma__','mep_portal','mep_projects','CREATE DATABASE IF NOT EXISTS `test`','po_number']:
    require(forbidden not in database,f'Obsolete or private database content remains: {forbidden}')
for required_schema in ['`manufacturer` varchar(180)','`installed_quantity` decimal(12,2)',"enum('A','B','C','D','UR','P')",'`auth_version` int unsigned','`expected_delivery_date` date','`actual_delivery_date` date',"`completion_date_source` enum('auto','manual')",'`currency` char(3)',"`status` enum('pending','processing','completed','failed')"]:
    require(required_schema in database,f'Latest database schema item missing: {required_schema}')

management_text=(ROOT/'app/Models/ManagementAlert.php').read_text(errors='replace')+(ROOT/'app/Services/ManagementDashboard.php').read_text(errors='replace')
require('expected_delivery_date' in management_text,'Management queries must use the current expected delivery date')
require("Good Received / Delivered" in management_text,'Management queries do not use the current Procurement delivered status')
workplan_action=(ROOT/'app/Actions/workplan.php').read_text(errors='replace')
require('VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)' in workplan_action,'Work Plan INSERT does not have 19 placeholders')

auth=(ROOT/'includes/auth.php').read_text(errors='replace')
require(auth.count('function recalculate_boq_progress(')==1,'recalculate_boq_progress must be declared exactly once')
bootstrap=(ROOT/'app/bootstrap.php').read_text(errors='replace')
require(bootstrap.find('ErrorHandler::register()') < bootstrap.find("require_once __DIR__ . '/../includes/auth.php'"),'Error handler must register before legacy helpers load')
handler=(ROOT/'app/Core/ErrorHandler.php').read_text(errors='replace')
for diagnostic in ['register_shutdown_function','sourceExcerpt','possible_cause','submitted_fields','MEP_HANDLER','MEP_ACTION_FILE']:
    require(diagnostic in handler,f'Error diagnostics missing {diagnostic}')

routes=(ROOT/'routes/web.php').read_text()+(ROOT/'routes/api.php').read_text()
require("$router->get('logout'" not in routes,'Logout must not accept GET requests')
require("$router->post('logout'" in routes,'POST logout route is missing')
registered=set(re.findall(r"->(?:get|post|match)\([^\n]*?'([^']+)'",routes))
for wrapper in ROOT.glob('*.php'):
    text=wrapper.read_text(errors='replace')
    match=re.search(r"\$_GET\['route'\]\s*=\s*'([^']+)'",text)
    if match: require(match.group(1) in registered,f'{wrapper.name} targets unregistered route {match.group(1)}')

for path in ROOT.rglob('*.php'):
    text=path.read_text(errors='replace')
    require(text.count('<?php')+text.count('<?')>=text.count('?>'),f'PHP tag imbalance in {path.relative_to(ROOT)}')

header=(ROOT/'includes/header.php').read_text(errors='replace')
require('app_version_label()' in header and 'method="post" action="logout.php"' in header,'Visible version identity or POST logout form is missing')
logout_action=(ROOT/'app/Actions/logout.php').read_text(errors='replace')
require("REQUEST_METHOD'] !== 'POST'" in logout_action and 'verify_csrf()' in logout_action,'Logout is not restricted to CSRF-protected POST requests')
auth=(ROOT/'includes/auth.php').read_text(errors='replace')
require('auth_version' in auth and 'recalculate_progress_actual_start' in auth,'Session revocation or Actual Start recalculation is missing')
require("return 'Version '." in auth and "APP_STAGE" not in auth.split('function app_version_label',1)[1].split('}',1)[0],'Visible version label must not include a stage prefix')
workplan=(ROOT/'app/Actions/workplan.php').read_text(errors='replace')
require("dirname(__DIR__, 2)" in workplan and "'Work Pending'         => 0" in workplan,'Work Plan root path or pending percentage regressed')
require('Installed quantity cannot be negative' in workplan,'Work Plan installed quantity validation is missing')
require("!is_numeric($installedQtyRaw)" in workplan and 'Cumulative installed quantity cannot exceed' in workplan,'Work Plan quantity parsing or BOQ cap is missing')
require('MAX(installed_quantity)' in auth,'Quantity-based progress must use one cumulative maximum across stages')
require("$profile='Installed Quantity'" in auth and "$totalBoqQty>0" in auth,'Approved installed quantity must take precedence over workflow profiles')
require('min(99.99,round($rawPct,2))' in auth,'Asymmetric item-progress rounding is missing')
calculator=(ROOT/'app/Services/ProgressCalculator.php').read_text(errors='replace')
require('return 0.00' in calculator and 'min(99.99,round($progress,2))' in calculator,'Zero-weight protection or asymmetric calculator rounding is missing')
aggregate_sources='\n'.join((ROOT/path).read_text(errors='replace') for path in ['app/Actions/project_progress.php','app/Actions/projects.php','app/Actions/select_project.php','app/Actions/report_pdf.php','app/Services/ManagementDashboard.php'])
require('LEAST(99.99,ROUND(' in aggregate_sources and ',1),0)' not in aggregate_sources,'Two-decimal asymmetric aggregate rounding is incomplete')
require('`percentage_complete` decimal(5,2)' in database and '`planned_percentage` decimal(5,2)' in database and '`completion_percentage` decimal(5,2)' in database,'DECIMAL(5,2) progress schema is incomplete')
workplan_view=(ROOT/'app/Views/workplan/index.php').read_text(errors='replace')
for obsolete_pending in ["'Work Pending': 1", "?? 1", "|| 1"]:
    require(obsolete_pending not in workplan_view,f'Work Pending 1% fallback remains: {obsolete_pending}')
require("'Work Pending'         => 0" in workplan,'Work Pending is not mapped to zero percent server-side')
require('valid_date_value($dateValue)' in workplan and 'dates_in_order($plannedStart,$plannedFinish)' in workplan and '$installedQty < 0' in workplan,'Work Plan chronology or non-negative quantity validation is incomplete')
projects_action=(ROOT/'app/Actions/projects.php').read_text(errors='replace')
require('valid_date_value($data[6])' in projects_action and 'dates_in_order($data[6],$data[7])' in projects_action,'Project date validation is incomplete')
submittal_action=(ROOT/'app/Actions/submittals.php').read_text(errors='replace')
require("$status!=='P'&&!$submittedDate" in submittal_action and '$approvedDate&&!$submittedDate' in submittal_action,'Submittal date lifecycle validation is incomplete')
require('valid_date_value($submittedDate)' in submittal_action and 'dates_in_order($submittedDate,$approvedDate)' in submittal_action,'Submittal date format or chronology validation is incomplete')
procurement_schema=re.search(r'CREATE TABLE `procurement` \((.*?)\) ENGINE=',database,re.S|re.I)
require(procurement_schema is not None and all(name in procurement_schema.group(1) for name in ['`po_date`','`required_date`','`expected_delivery_date`','`actual_delivery_date`']),'Procurement delivery dates are not in the procurement table')
procurement_view=(ROOT/'app/Views/procurement/index.php').read_text(errors='replace')
require(all(name in procurement_view for name in ['po_date','required_date','expected_delivery_date','actual_delivery_date']),'Procurement date fields are missing from the form or detail view')
require("safe_document_url($masFileLink)" in (ROOT/'app/Actions/submittals.php').read_text(errors='replace'),'MAS URL scheme validation is missing')
submittal_view=(ROOT/'app/Views/submittals/index.php').read_text(errors='replace')
require(submittal_view.count('safe_document_href(')>=3,'Legacy MAS links are not filtered at every render path')
require((ROOT/'tools/.htaccess').read_text().strip()=='Require all denied','tools directory is not blocked from web access')
require('app|routes|includes|tools' in (ROOT/'.htaccess').read_text(errors='replace'),'Root Apache rule does not block internal source and tools')
require((ROOT/'database_upgrade.sql').is_file() and not (ROOT/'database_migrations').exists(),'Exactly one consolidated database upgrade file is required')
upgrade=(ROOT/'database_upgrade.sql').read_text(errors='replace')
for migration_id in ['003_001','006_001','007_001']:
    require(migration_id in upgrade,f'Consolidated database upgrade is missing {migration_id}')
require('password_reset_tokens' in database and '`token_hash` binary(32)' in database,'One-time password reset token schema is missing')
require('MEP_SMTP_HOST' in (ROOT/'.env.example').read_text(errors='replace'),'SMTP environment template is missing')
env_example=(ROOT/'.env.example').read_text(errors='replace')
storage=(ROOT/'app/Services/EvidenceStorage.php').read_text(errors='replace')
workplan_view=(ROOT/'app/Views/workplan/index.php').read_text(errors='replace')
queue_worker=(ROOT/'tools/process_queues.php').read_text(errors='replace')
require(all(key in env_example for key in ['MEP_EVIDENCE_STORAGE_DRIVER=s3','MEP_EVIDENCE_S3_ENDPOINT','MEP_EVIDENCE_S3_BUCKET','MEP_EVIDENCE_S3_ACCESS_KEY','MEP_EVIDENCE_S3_SECRET_KEY']),'S3 environment template is incomplete')
require(all(key in storage for key in ['AWS4-HMAC-SHA256','presignedGet','x-amz-meta-sha256',"'PUT'", "'HEAD'", "'DELETE'"]),'S3-compatible evidence adapter or integrity verification is incomplete')
require('evidence_url(' in workplan_view and "'uploads/workplan/'" not in workplan_view,'Work Plan view does not resolve evidence through the storage adapter')
require('evidence_storage()' in queue_worker and "'/uploads/workplan/'" not in queue_worker,'Cleanup worker bypasses the evidence-storage adapter')
require("item_type='Measurable Item'" in workplan and 'boq_ref_no=?' in workplan,'Work Plan references are not derived and cross-checked server-side')
require('selectedSubmittal' in procurement_action and 'originalRow' in procurement_action,'Procurement reference derivation or old/new recalculation is missing')
require("status IN ('A','B')" in procurement_action,'Procurement must resolve only approved MAS references')
require('oldBoqNo' in progress_action and 'newBoqNo' in progress_action,'BOQ edits do not recalculate both old and new references')
require('MAX(installed_quantity)' in auth and "work_plan_status<>'Work Pending'" in auth,'Cumulative installed quantity is not protected against repeated stage counting')
require("originalRow['boq_no']" in workplan and workplan.count('recalculate_boq_progress(')>=3,'Work Plan does not recalculate old and new BOQ references')
require("originalRow['boq_ref_no']" in procurement_action and procurement_action.count('recalculate_boq_progress(')>=3,'Procurement does not recalculate old and new BOQ references')
require('hash_file(' in workplan and 'putUploaded(' in workplan,'Work Plan upload checksum or storage verification is missing')
require('is_uploaded_file(' in workplan and "($dimensions['mime']??'') !== $mime" in workplan and '$upload[\'size\']<=0' in workplan,'Uploaded-file origin, size, or decoded MIME agreement validation is missing')
require(all(mime in workplan for mime in ["'image/jpeg'", "'image/png'", "'image/gif'", "'image/webp'"]),'Image MIME allowlist is incomplete')
uploads_htaccess=(ROOT/'uploads/.htaccess').read_text(errors='replace')
require('FilesMatch' in uploads_htaccess and 'php|phtml|phar' in uploads_htaccess and 'Require all denied' in uploads_htaccess,'Executable files are not blocked in the upload tree')
require('remove_or_queue_evidence(' in workplan and 'file_cleanup_queue' in auth,'Recoverable post-commit evidence cleanup is missing')
ajax_boq=(ROOT/'app/Actions/ajax_boq_list.php').read_text(errors='replace')
ajax_progress=(ROOT/'app/Actions/ajax_progress_list.php').read_text(errors='replace')
require('progress_id' in submittal_action and 'duplicate_count' in ajax_boq and 'duplicate_count' in ajax_progress,'Stable BOQ link or duplicate handling is missing')
require('data-duplicate' in submittal_view and 'data-duplicate' in workplan_view and 'Resolve the duplicate measurable items' in workplan_view,'Duplicate BOQ references are not disabled gracefully in both selectors')
for json_view in ['procurement/index.php','workplan/index.php','submittals/index.php','project_members/index.php']:
    content=(ROOT/'app/Views'/json_view).read_text(errors='replace')
    require('JSON_HEX_APOS' in content and 'JSON_HEX_QUOT' in content,f'HTML-safe JSON flags missing from {json_view}')
require('JSON_HEX_TAG' in workplan_view and 'JSON_HEX_AMP' in workplan_view and "svField('Prepared by', d.responsible)" in workplan_view and "function svField(label, value" in workplan_view and "escH(value || '—')" in workplan_view,'Safe Work Plan user-name rendering is incomplete')
delete_block=workplan[workplan.find("if ($action === 'delete')"):workplan.find("$id = (int)($_POST['id']",workplan.find("if ($action === 'delete')"))]
require(delete_block.find('$pdo->commit();')>=0 and delete_block.find('remove_or_queue_evidence(')>delete_block.find('$pdo->commit();'),'Work Plan evidence deletion must occur after database commit')
require("APP_ENV==='development'" in config and "MEP_ALLOW_SELF_SIGNUP" in config and "$isLocalRequest?'true':'false'" in config,'Local diagnostics or production debug/signup hardening is missing')
require('APP_TIMEZONE' in config and 'Asia/Riyadh' in config,'Riyadh display timezone policy is missing')
assignment_api=(ROOT/'app/Controllers/ProjectAssignmentApiController.php').read_text(errors='replace')
require("PRECONDITION_REQUIRED" in assignment_api and "INVALID_ETAG" in assignment_api and "428" in assignment_api,'Quoted ETag precondition handling is missing')
require((ROOT/'tools/process_queues.php').is_file(),'Queue worker is missing')
login_action=(ROOT/'app/Actions/login.php').read_text(errors='replace')
require(all(key in login_action for key in ["'pair'=>5","'email'=>10","'ip'=>25"]),'Multi-key login throttling thresholds are missing')
for obsolete in ['BoqController.php','ExportController.php','ManualOverrideController.php']:
    require(not (ROOT/'app/Controllers'/obsolete).exists(),f'Obsolete controller remains: {obsolete}')
for obsolete in ['BoqMaster.php','BoqConflict.php','ManualOverride.php','WorkflowEvent.php','WorkflowException.php','Procurement.php','Submittal.php']:
    require(not (ROOT/'app/Models'/obsolete).exists(),f'Obsolete PostgreSQL/UUID model remains: {obsolete}')

if errors:
    print('\n'.join('ERROR: '+e for e in errors))
    sys.exit(1)
print(f'OK: Version 007.4 conversion controls, UX, upload integrity, BOQ linkage, security and calculations validated ({len(action_files)} actions, {len(registered)} routes).')
