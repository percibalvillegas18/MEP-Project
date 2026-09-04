<?php
declare(strict_types=1);

/*
 * Version 007.4 runs on the MySQL/MariaDB schema in database.sql.
 * Every value can be overridden by the web-server environment, so credentials
 * never need to be committed to the project.
 */
$versionFile = __DIR__ . '/VERSION.txt';
$versionValue=is_readable($versionFile)?trim((string)file_get_contents($versionFile)):'007.4';
define('APP_VERSION',preg_match('/^\d{3}(?:\.\d+)?$/',$versionValue)?$versionValue:'007.4');
$serverName=strtolower((string)($_SERVER['SERVER_NAME']??''));
$hostName=strtolower(preg_replace('/:\d+$/','',(string)($_SERVER['HTTP_HOST']??''))??'');
$remoteAddress=(string)($_SERVER['REMOTE_ADDR']??'');
$isLocalRequest=in_array($serverName,['localhost','127.0.0.1','::1'],true)||in_array($hostName,['localhost','127.0.0.1','::1'],true)||in_array($remoteAddress,['127.0.0.1','::1'],true);
$configuredEnvironment=getenv('MEP_APP_ENV');
define('APP_ENV',$configuredEnvironment!==false&&$configuredEnvironment!==''?(string)$configuredEnvironment:($isLocalRequest?'development':'production'));
$configuredDebug=getenv('MEP_APP_DEBUG');
define('APP_DEBUG',APP_ENV==='development'&&filter_var($configuredDebug!==false?(string)$configuredDebug:($isLocalRequest?'true':'false'),FILTER_VALIDATE_BOOL));
define('ALLOW_SELF_SIGNUP', filter_var(getenv('MEP_ALLOW_SELF_SIGNUP') ?: (getenv('ALLOW_SELF_SIGNUP') ?: 'false'), FILTER_VALIDATE_BOOL));
define('MAX_UPLOAD_BYTES', max(1048576,(int)(getenv('MEP_MAX_UPLOAD_BYTES') ?: 10485760)));
define('SESSION_IDLE_SECONDS',max(900,(int)(getenv('MEP_SESSION_IDLE_SECONDS') ?: 7200)));
define('PASSWORD_RESET_MINUTES',max(5,min(120,(int)(getenv('MEP_PASSWORD_RESET_MINUTES') ?: 30))));
define('APP_TIMEZONE',getenv('MEP_APP_TIMEZONE') ?: 'Asia/Riyadh');
define('EVIDENCE_STORAGE_DRIVER',strtolower(getenv('MEP_EVIDENCE_STORAGE_DRIVER') ?: 'local'));
define('EVIDENCE_OBJECT_PREFIX',trim((string)(getenv('MEP_EVIDENCE_OBJECT_PREFIX') ?: 'mep/workplan'),'/'));
define('EVIDENCE_S3_ENDPOINT',rtrim((string)(getenv('MEP_EVIDENCE_S3_ENDPOINT') ?: 'https://s3.amazonaws.com'),'/'));
define('EVIDENCE_S3_REGION',(string)(getenv('MEP_EVIDENCE_S3_REGION') ?: 'us-east-1'));
define('EVIDENCE_S3_BUCKET',(string)(getenv('MEP_EVIDENCE_S3_BUCKET') ?: ''));
define('EVIDENCE_S3_ACCESS_KEY',(string)(getenv('MEP_EVIDENCE_S3_ACCESS_KEY') ?: ''));
define('EVIDENCE_S3_SECRET_KEY',(string)(getenv('MEP_EVIDENCE_S3_SECRET_KEY') ?: ''));
define('EVIDENCE_S3_PATH_STYLE',filter_var(getenv('MEP_EVIDENCE_S3_PATH_STYLE') ?: 'false',FILTER_VALIDATE_BOOL));
define('EVIDENCE_PUBLIC_BASE_URL',rtrim((string)(getenv('MEP_EVIDENCE_PUBLIC_BASE_URL') ?: ''),'/'));
define('EVIDENCE_SIGNED_URL_TTL',max(60,min(3600,(int)(getenv('MEP_EVIDENCE_SIGNED_URL_TTL') ?: 900))));
if(EVIDENCE_STORAGE_DRIVER!=='local'&&(!in_array(EVIDENCE_STORAGE_DRIVER,['s3','object'],true)||EVIDENCE_S3_BUCKET===''||EVIDENCE_S3_ACCESS_KEY===''||EVIDENCE_S3_SECRET_KEY===''))throw new \RuntimeException('S3 evidence storage is selected but endpoint, bucket, access key, or secret key is missing.');
define('DEFAULT_CURRENCY',strtoupper(getenv('MEP_DEFAULT_CURRENCY') ?: 'SAR'));
date_default_timezone_set(APP_TIMEZONE);

define('DB_CONNECTION',strtolower(getenv('MEP_DB_CONNECTION') ?: (getenv('DB_CONNECTION') ?: 'mysql')));
define('DB_HOST',getenv('MEP_DB_HOST') ?: (getenv('DB_HOST') ?: '127.0.0.1'));
define('DB_PORT',getenv('MEP_DB_PORT') ?: (getenv('DB_PORT') ?: '3306'));
define('DB_DATABASE',getenv('MEP_DB_NAME') ?: (getenv('DB_DATABASE') ?: 'mep_database'));
define('DB_USERNAME',getenv('MEP_DB_USER') ?: (getenv('DB_USERNAME') ?: 'root'));
define('DB_PASSWORD',getenv('MEP_DB_PASS') !== false ? (string)getenv('MEP_DB_PASS') : (string)(getenv('DB_PASSWORD') ?: ''));
if(DB_CONNECTION!=='mysql')throw new \RuntimeException('Only the MySQL/MariaDB PDO driver is supported. Set MEP_DB_CONNECTION=mysql.');
if(APP_ENV==='production'&&DB_USERNAME==='root'&&DB_PASSWORD===''&&!filter_var(getenv('MEP_ALLOW_INSECURE_LOCAL_DB')?:'false',FILTER_VALIDATE_BOOL))throw new \RuntimeException('Production database credentials are not configured. Set MEP_DB_USER and MEP_DB_PASS, or use MEP_APP_ENV=development for local XAMPP testing.');

define('SMTP_HOST',getenv('MEP_SMTP_HOST') ?: '');define('SMTP_PORT',(int)(getenv('MEP_SMTP_PORT') ?: 587));
define('SMTP_USER',getenv('MEP_SMTP_USER') ?: '');define('SMTP_PASS',getenv('MEP_SMTP_PASS') ?: '');
define('SMTP_FROM',getenv('MEP_SMTP_FROM') ?: SMTP_USER);define('SMTP_FROM_NAME',getenv('MEP_SMTP_FROM_NAME') ?: 'MEP Projects Portal');
define('APP_BASE_URL',rtrim((string)(getenv('MEP_APP_BASE_URL') ?: ''),'/'));

$dsn = DB_CONNECTION . ':host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_DATABASE . ';charset=utf8mb4';
try {
    $pdo = new \PDO($dsn, DB_USERNAME, DB_PASSWORD, [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $pdo->exec("SET time_zone = '+00:00'");
} catch (\PDOException $e) {
    error_log('Database Connection Error: ' . $e->getMessage());
    throw new \RuntimeException('Unable to connect to the application database.', 0, $e);
}

if(session_status()===PHP_SESSION_NONE){$https=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';session_name('MEPSESSID');ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');ini_set('session.cookie_httponly','1');ini_set('session.cookie_samesite','Lax');ini_set('session.cookie_secure',($https||APP_ENV==='production')?'1':'0');session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$https||APP_ENV==='production','httponly'=>true,'samesite'=>'Lax']);session_start();}
if(!headers_sent()){header('X-Content-Type-Options: nosniff');header('X-Frame-Options: SAMEORIGIN');header('Referrer-Policy: strict-origin-when-cross-origin');header('Permissions-Policy: camera=(), microphone=(), geolocation=()');if(APP_ENV==='production'&&!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')header('Strict-Transport-Security: max-age=31536000; includeSubDomains');}

return $pdo;
