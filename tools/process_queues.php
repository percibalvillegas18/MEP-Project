<?php
declare(strict_types=1);

if(PHP_SAPI!=='cli'){http_response_code(403);exit("CLI only\n");}
$pdo=require dirname(__DIR__).'/config.php';
$maxAttempts=max(1,min(20,(int)(getenv('MEP_QUEUE_MAX_ATTEMPTS')?:5)));
$batch=max(1,min(100,(int)(getenv('MEP_QUEUE_BATCH_SIZE')?:25)));
$storage=evidence_storage();

$pdo->beginTransaction();
$rows=$pdo->query("SELECT * FROM file_cleanup_queue WHERE status IN ('pending','failed') AND attempts<{$maxAttempts} AND (next_attempt_at IS NULL OR next_attempt_at<=UTC_TIMESTAMP()) ORDER BY id LIMIT {$batch} FOR UPDATE")->fetchAll();
foreach($rows as $row){
    $pdo->prepare("UPDATE file_cleanup_queue SET status='processing',attempts=attempts+1 WHERE id=?")->execute([$row['id']]);
    $ok=$storage->delete((string)$row['relative_path']);
    if($ok)$pdo->prepare("UPDATE file_cleanup_queue SET status='completed',completed_at=UTC_TIMESTAMP(),last_error=NULL WHERE id=?")->execute([$row['id']]);
    else $pdo->prepare("UPDATE file_cleanup_queue SET status=IF(attempts>=?,'failed','pending'),last_error='evidence deletion failed',next_attempt_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL LEAST(60,POW(2,attempts)) MINUTE) WHERE id=?")->execute([$maxAttempts,$row['id']]);
}
$pdo->exec("UPDATE rbac_outbox SET status='completed',processed_at=UTC_TIMESTAMP(6),attempts=attempts+1,last_error=NULL WHERE status IN ('pending','failed') AND attempts<{$maxAttempts} AND (next_attempt_at IS NULL OR next_attempt_at<=UTC_TIMESTAMP(6)) ORDER BY id LIMIT {$batch}");
$pdo->commit();
echo 'Processed cleanup='.count($rows).PHP_EOL;
