<?php
require_once dirname(__DIR__,2).'/includes/auth.php';

$plainToken=trim((string)($_POST['token']??$_GET['token']??''));
$error='';$success=false;$validToken=false;
if(preg_match('/^[a-f0-9]{64}$/',$plainToken)){
    $lookup=$pdo->prepare("SELECT t.id,t.user_id,u.email FROM password_reset_tokens t JOIN users u ON u.id=t.user_id WHERE t.token_hash=? AND t.used_at IS NULL AND t.expires_at>NOW() AND u.status='active' LIMIT 1");
    $lookup->execute([hash('sha256',$plainToken,true)]);
    $resetRecord=$lookup->fetch();
    $validToken=(bool)$resetRecord;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $newPassword=(string)($_POST['new_password']??'');
    $confirmation=(string)($_POST['confirm_password']??'');
    if(!$validToken){$error='This password reset link is invalid, expired, or already used.';}
    elseif(strlen($newPassword)<12){$error='Use at least 12 characters for the new password.';}
    elseif($newPassword!==$confirmation){$error='The password confirmation does not match.';}
    else{
        $pdo->beginTransaction();
        try{
            $claim=$pdo->prepare("UPDATE password_reset_tokens SET used_at=NOW() WHERE id=? AND used_at IS NULL AND expires_at>NOW()");
            $claim->execute([(int)$resetRecord['id']]);
            if($claim->rowCount()!==1)throw new RuntimeException('Reset token was already claimed.');
            $pdo->prepare("UPDATE users SET password=?,auth_version=auth_version+1,updated_at=NOW() WHERE id=?")
                ->execute([password_hash($newPassword,PASSWORD_DEFAULT),(int)$resetRecord['user_id']]);
            $pdo->prepare("UPDATE password_reset_tokens SET used_at=COALESCE(used_at,NOW()) WHERE user_id=? AND used_at IS NULL")
                ->execute([(int)$resetRecord['user_id']]);
            audit_event($pdo,'password_reset_completed','users',(int)$resetRecord['user_id'],null,'Password reset link completed');
            $pdo->commit();$success=true;$validToken=false;
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('Password reset completion failed: '.$e->getMessage());$error='The password could not be changed. Request a new reset link.';}
    }
}
