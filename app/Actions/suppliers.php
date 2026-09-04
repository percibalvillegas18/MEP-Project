<?php
require_once dirname(__DIR__, 2) . '/includes/auth.php'; require_login();
if($_SERVER['REQUEST_METHOD']==='POST'){
 require_role('admin','project_manager','project_engineer','mep_engineer');
 verify_csrf();$action=$_POST['action']??'add';
 if($action==='delete'){require_admin();$sid=(int)$_POST['id'];$pdo->prepare("DELETE FROM suppliers WHERE id=?")->execute([$sid]);audit_event($pdo,'delete','suppliers',$sid,null,'Supplier deleted');flash('success','Supplier deleted.');redirect('suppliers.php');}
 $id=(int)($_POST['id']??0);$d=[trim($_POST['company_name']??''),trim($_POST['contact_name']??''),trim($_POST['phone']??''),trim($_POST['whatsapp']??''),trim($_POST['email']??''),trim($_POST['website']??''),trim($_POST['category']??''),trim($_POST['location']??''),trim($_POST['notes']??'')];
 if($d[0]===''){flash('error','Company name is required.');redirect('suppliers.php');}
 if($id){$d[]=$id;$pdo->prepare("UPDATE suppliers SET company_name=?,contact_name=?,phone=?,whatsapp=?,email=?,website=?,category=?,location=?,notes=? WHERE id=?")->execute($d);audit_event($pdo,'update','suppliers',$id,null,'Supplier updated');flash('success','Supplier updated.');}
 else{$pdo->prepare("INSERT INTO suppliers(company_name,contact_name,phone,whatsapp,email,website,category,location,notes) VALUES(?,?,?,?,?,?,?,?,?)")->execute($d);$id=(int)$pdo->lastInsertId();audit_event($pdo,'create','suppliers',$id,null,'Supplier created');flash('success','Supplier added.');}redirect('suppliers.php');
}
$edit=null;if(can_manage_suppliers()&&isset($_GET['edit'])){$st=$pdo->prepare("SELECT * FROM suppliers WHERE id=?");$st->execute([(int)$_GET['edit']]);$edit=$st->fetch();}
$q=trim($_GET['q']??'');if($q!==''){$st=$pdo->prepare("SELECT * FROM suppliers WHERE company_name LIKE ? OR category LIKE ? OR location LIKE ? ORDER BY company_name");$like="%$q%";$st->execute([$like,$like,$like]);$rows=$st->fetchAll();}else{$rows=$pdo->query("SELECT * FROM suppliers ORDER BY company_name")->fetchAll();}
$pageTitle='Supplier Database';
