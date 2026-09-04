<?php
declare(strict_types=1);

if(PHP_SAPI!=='cli'){http_response_code(403);exit("CLI only\n");}
$root=dirname(__DIR__);$failures=[];$passes=[];
$check=static function(bool $ok,string $label)use(&$failures,&$passes):void{($ok?$passes:$failures)[]=$label;echo($ok?'PASS: ':'FAIL: ').$label.PHP_EOL;};

$check(PHP_VERSION_ID>=80100,'PHP 8.1 or newer');
foreach(['pdo','pdo_mysql','fileinfo','gd','openssl'] as $extension)$check(extension_loaded($extension),"PHP extension {$extension}");
$check(is_file($root.'/tools/.htaccess')&&trim((string)file_get_contents($root.'/tools/.htaccess'))==='Require all denied','tools/.htaccess denies web access');
$check(str_contains((string)file_get_contents($root.'/.htaccess'),'app|routes|includes|tools'),'root .htaccess blocks internal directories');
$check(is_file($root.'/database_upgrade.sql')&&!is_dir($root.'/database_migrations'),'one consolidated database upgrade file');
$check(trim((string)file_get_contents($root.'/VERSION.txt'))==='007.4','running release marker is 007.4');

$host=getenv('MEP_DB_HOST')?:'127.0.0.1';$port=getenv('MEP_DB_PORT')?:'3306';$name=getenv('MEP_DB_NAME')?:'mep_database';
$user=getenv('MEP_DB_USER')?:'root';$pass=getenv('MEP_DB_PASS')!==false?(string)getenv('MEP_DB_PASS'):'';
try{
    $db=new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $engine=(string)$db->query('SELECT VERSION()')->fetchColumn();$check((bool)preg_match('/(?:MariaDB|^(?:8|9)\.)/i',$engine),"supported MySQL/MariaDB server ({$engine})");
    $required=['users','projects','project_members','roles','permissions','role_permissions','project_role_assignments','password_reset_tokens','project_progress','submittals','procurement','workplan','workplan_photos','file_cleanup_queue','audit_logs','schema_migrations'];
    $s=$db->query('SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE()');$tables=array_column($s->fetchAll(),'table_name');
    foreach($required as $table)$check(in_array($table,$tables,true),"database table {$table}");
    $columns=[];$s=$db->query('SELECT table_name,column_name FROM information_schema.columns WHERE table_schema=DATABASE()');foreach($s as $row)$columns[$row['table_name']][]=$row['column_name'];
    foreach(['auth_version'] as $column)$check(in_array($column,$columns['users']??[],true),"users.{$column}");
    foreach(['completion_date_source','unit','item_type'] as $column)$check(in_array($column,$columns['project_progress']??[],true),"project_progress.{$column}");
    foreach(['currency'] as $column)$check(in_array($column,$columns['procurement']??[],true),"procurement.{$column}");
    foreach(['status','attempts','next_attempt_at','last_error'] as $column)$check(in_array($column,$columns['rbac_outbox']??[],true),"rbac_outbox.{$column}");
    foreach(['idempotency_key','status','attempts','next_attempt_at'] as $column)$check(in_array($column,$columns['file_cleanup_queue']??[],true),"file_cleanup_queue.{$column}");
    foreach(['po_date','required_date','expected_delivery_date','actual_delivery_date'] as $column)$check(in_array($column,$columns['procurement']??[],true),"procurement.{$column}");
    foreach(['work_status_image_before_checksum'] as $column)$check(in_array($column,$columns['workplan']??[],true),"workplan.{$column}");
    foreach(['checksum'] as $column)$check(in_array($column,$columns['workplan_photos']??[],true),"workplan_photos.{$column}");
    foreach(['progress_id'] as $column)$check(in_array($column,$columns['submittals']??[],true),"submittals.{$column}");
    foreach(['event_uuid','before_state','after_state','event_hash'] as $column)$check(in_array($column,$columns['audit_logs']??[],true),"audit_logs.{$column}");
    $check((int)$db->query("SELECT COUNT(*) FROM roles WHERE active=1 AND scope='project'")->fetchColumn()>=5,'project role catalogue seeded');
    $check((int)$db->query("SELECT COUNT(*) FROM permissions WHERE active=1")->fetchColumn()>=15,'permission catalogue seeded');
    $check((int)$db->query("SELECT COUNT(*) FROM project_members pm LEFT JOIN project_role_assignments a ON a.project_id=pm.project_id AND a.user_id=pm.user_id AND a.active=1 WHERE a.id IS NULL")->fetchColumn()===0,'legacy memberships backfilled');
}catch(Throwable $e){$failures[]='database connection/schema: '.$e->getMessage();echo'FAIL: database connection/schema: '.$e->getMessage().PHP_EOL;}

echo PHP_EOL.count($passes).' passed; '.count($failures).' failed.'.PHP_EOL;
exit($failures?1:0);
