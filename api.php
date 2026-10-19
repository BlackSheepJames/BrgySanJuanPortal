<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
start_session();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
$pdo = null;
try { $pdo = db(); } catch (Throwable $e) { json_response(['error'=>'Database is not set up. Open index.php to initialize the workspace.'], 503); }
$isMultipart = str_contains(strtolower((string)($_SERVER['CONTENT_TYPE'] ?? '')), 'multipart/form-data');
$input = $isMultipart ? $_POST : (json_decode(file_get_contents('php://input'), true) ?: []);
if ($isMultipart && isset($input['record'])) $input['record'] = json_decode((string)$input['record'], true) ?: [];
$action = (string)($_GET['action'] ?? $input['action'] ?? '');

if ($action === 'login') {
    $lock = $_SESSION['login_lock'] ?? 0;
    if ($lock > time()) json_response(['error'=>'Too many sign-in attempts. Wait a few minutes and try again.'], 429);
    $identifier=trim((string)($input['identifier']??$input['username']??''));
    $password=(string)($input['password']??'');
    if (!password_meets_policy($password)) {
        json_response(['error'=>'Password must be at least 8 characters and include an uppercase letter, a number, and a symbol.'],422);
    }
    if (str_contains($identifier, '@') && !is_gmail_address($identifier)) {
        json_response(['error'=>'Use your @gmail.com email address or your username.'],422);
    }
    $stmt = $pdo->prepare('SELECT id,name,username,email,password_hash,role,active FROM users WHERE username=? OR email=? LIMIT 1');
    $stmt->execute([$identifier,$identifier]); $row = $stmt->fetch();
    if (!$row || !$row['active'] || !password_verify($password, $row['password_hash'])) {
        $_SESSION['login_attempts'] = (int)($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= 5) { $_SESSION['login_lock'] = time() + 300; $_SESSION['login_attempts'] = 0; }
        json_response(['error'=>'Username or password is incorrect.'], 401);
    }
    session_regenerate_id(true); $_SESSION['user'] = ['id'=>(int)$row['id'],'name'=>$row['name'],'username'=>$row['username'],'role'=>$row['role']];
    $_SESSION['login_attempts']=0; $_SESSION['login_lock']=0; $_SESSION['csrf'] = bin2hex(random_bytes(32)); audit($pdo, 'signed_in', 'user', $row['id']);
    json_response(['user'=>$_SESSION['user'],'csrf'=>$_SESSION['csrf']]);
}
if ($action === 'register') {
    $first=trim((string)($input['first_name']??''));$middle=trim((string)($input['middle_name']??''));$last=trim((string)($input['last_name']??''));$suffix=trim((string)($input['suffix']??''));
    $phone=trim((string)($input['phone']??''));$email=strtolower(trim((string)($input['email']??'')));$address=trim((string)($input['address']??''));$password=(string)($input['password']??'');
    $birth=trim((string)($input['birth_date']??''));
    if(mb_strlen($first)<1||mb_strlen($first)>90||mb_strlen($middle)>90||mb_strlen($last)<1||mb_strlen($last)>90||mb_strlen($suffix)>20||!is_gmail_address($email)||mb_strlen($email)>160||!preg_match('/^[0-9]{7,15}$/',$phone)||mb_strlen($address)<4||!password_meets_policy($password)||$password!==(string)($input['confirm_password']??'')||($birth!==''&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$birth)||strtotime($birth)===false||$birth>date('Y-m-d')))) json_response(['error'=>'Complete the required fields. Use a @gmail.com email, a 7–15 digit phone number, and a matching password with at least 8 characters, an uppercase letter, a number, and a symbol.'],422);
    $gender=(string)($input['gender']??'Prefer not to say');if(!in_array($gender,['Female','Male','Other','Prefer not to say'],true))$gender='Prefer not to say';
    $sectors=array_values(array_intersect((array)($input['sectors']??[]),['Senior Citizen','PWD','Solo Parent','4Ps Beneficiary','Youth','Indigenous People']));
    try {
        $pdo->beginTransaction();
        $base=preg_replace('/[^a-z0-9._-]/','',strtolower((string)strstr($email,'@',true)));$base=substr($base?:'resident',0,45);if(strlen($base)<3)$base='resident';$username=$base;
        $check=$pdo->prepare('SELECT COUNT(*) FROM users WHERE username=?');$check->execute([$username]);if((int)$check->fetchColumn()>0)$username=substr($base,0,46).'-'.strtolower(bin2hex(random_bytes(3)));
        $fullName=implode(' ',array_filter([$first,$middle,$last,$suffix],fn($v)=>$v!==''));
        $stmt=$pdo->prepare("INSERT INTO users(name,username,email,phone,password_hash,role) VALUES(?,?,?,?,?,'resident')");$stmt->execute([$fullName,$username,$email,$phone,password_hash($password,PASSWORD_DEFAULT)]);$userId=(int)$pdo->lastInsertId();
        $code='SJ-'.date('y').'-'.strtoupper(bin2hex(random_bytes(3)));
        $resident=$pdo->prepare('INSERT INTO residents(resident_code,first_name,middle_name,last_name,suffix,birth_date,gender,civil_status,occupation,phone,email,purok,address,household_code,household_role,voter_status,sectors,user_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $resident->execute([$code,$first,$middle?:null,$last,$suffix?:null,($input['birth_date']??'')?:null,$gender,($input['civil_status']??'')?:null,($input['occupation']??'')?:null,$phone,$email,($input['purok']??'')?:null,$address,($input['household_code']??'')?:null,($input['household_role']??'')?:null,!empty($input['voter_status'])?1:0,json_encode($sectors,JSON_UNESCAPED_UNICODE),$userId]);$residentId=(int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO address_history(resident_id,address,purok,moved_in) VALUES(?,?,?,CURDATE())')->execute([$residentId,$address,($input['purok']??'')?:null]);
        $pdo->commit();session_regenerate_id(true);$_SESSION['user']=['id'=>$userId,'name'=>$fullName,'username'=>$username,'role'=>'resident'];$_SESSION['csrf']=bin2hex(random_bytes(32));$_SESSION['last_activity']=time();audit($pdo,'registered','resident',$residentId,['resident_code'=>$code]);
        json_response(['user'=>$_SESSION['user'],'csrf'=>$_SESSION['csrf']]);
    } catch(PDOException $e) { if($pdo->inTransaction())$pdo->rollBack();if((string)$e->getCode()==='23000')json_response(['error'=>'An account already uses that email address. Sign in or use another email.'],409);throw $e; }
}
if ($action === 'verify') {
    $token = (string)($_GET['token'] ?? '');
    $stmt = $pdo->prepare("SELECT document_no,document_type,resident_name,status,issued_at FROM documents WHERE verification_token=? AND status='Released' LIMIT 1"); $stmt->execute([$token]);
    $doc = $stmt->fetch(); json_response(['valid'=>(bool)$doc,'document'=>$doc ?: null]);
}
if ($action === 'logout') { $u = current_user(); if ($u) audit($pdo,'signed_out','user',$u['id']); $_SESSION=[]; session_destroy(); json_response(['ok'=>true]); }
$user = require_auth();
if (in_array($action, ['save','delete','staff_save','staff_status','announcement_save','announcement_cancel'], true)) {
    $csrf = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!$csrf || !hash_equals((string)($_SESSION['csrf'] ?? ''), $csrf)) json_response(['error'=>'Your session token has expired. Refresh and try again.'], 419);
}
$specs = [
 'residents'=>['table'=>'residents','roles'=>['admin','captain','secretary','tanod'],'fields'=>['resident_code','first_name','middle_name','last_name','suffix','birth_date','gender','civil_status','occupation','phone','email','purok','address','household_code','household_role','voter_status','sectors','notes','status'],'search'=>['resident_code','first_name','last_name','phone','household_code']],
 'documents'=>['table'=>'documents','roles'=>['admin','captain','secretary'],'fields'=>['resident_id','resident_name','document_type','purpose','fee','or_no','status'],'search'=>['document_no','resident_name','document_type','or_no']],
 'cases'=>['table'=>'cases','roles'=>['admin','captain','secretary','tanod'],'fields'=>['case_type','complainant','respondent','incident_date','location','narrative','status','lupon_officer','cfa_no'],'search'=>['case_no','case_type','complainant','respondent','status']],
 'hearings'=>['table'=>'hearings','roles'=>['admin','captain','secretary','tanod'],'fields'=>['case_id','hearing_at','hearing_type','venue','notes','outcome','status'],'search'=>['venue','hearing_type','status']],
 'displacements'=>['table'=>'displacements','roles'=>['admin','captain','secretary','tanod'],'fields'=>['household_code','family_name','family_size','origin_purok','shelter_id','displaced_at','returned_at','notes'],'search'=>['family_name','household_code','origin_purok']],
 'relief'=>['table'=>'relief_distributions','roles'=>['admin','captain','secretary','tanod'],'fields'=>['household_code','recipient_name','purok','package_type','quantity','signature_name'],'search'=>['reference_no','recipient_name','household_code','purok','package_type']],
 'health'=>['table'=>'health_records','roles'=>['admin','captain','health_worker'],'fields'=>['resident_id','patient_name','service_type','service_date','details','health_worker'],'search'=>['patient_name','service_type','health_worker']],
 'medicines'=>['table'=>'medicines','roles'=>['admin','captain','health_worker'],'fields'=>['name','category','quantity','unit','reorder_level','expiry_date'],'search'=>['name','category']],
 'aid'=>['table'=>'aid_distributions','roles'=>['admin','captain','secretary'],'fields'=>['recipient_name','household_code','aid_program','amount','assistance_date','signed_by','notes'],'search'=>['reference_no','recipient_name','household_code','aid_program']],
 'shelters'=>['table'=>'shelters','roles'=>['admin','captain','secretary'],'fields'=>['name','location','latitude','longitude','capacity','occupied','status'],'search'=>['name','location','status']],
];

if ($action === 'me') json_response(['user'=>$user,'csrf'=>$_SESSION['csrf'] ?? null]);
if ($action === 'my_profile') { $user=require_auth();$stmt=$pdo->prepare('SELECT resident_code,first_name,middle_name,last_name,suffix,birth_date,gender,civil_status,occupation,phone,email,purok,address,household_code,household_role,voter_status,sectors,created_at FROM residents WHERE user_id=? LIMIT 1');$stmt->execute([$user['id']]);$profile=$stmt->fetch();if(!$profile)json_response(['error'=>'Your resident profile is not linked. Please contact the barangay administrator.'],404);json_response(['profile'=>$profile]); }
if ($action === 'announcements') {
    json_response(['items'=>$pdo->query("SELECT a.id,a.title,a.body,a.starts_at,a.ends_at,a.location,a.author_id,a.author_role,a.status,a.created_at,u.name AS author_name FROM announcements a LEFT JOIN users u ON u.id=a.author_id WHERE a.status='Published' ORDER BY a.starts_at LIMIT 300")->fetchAll()]);
}
if ($action === 'announcement_save') {
    $user=require_roles(['admin','captain','secretary','health_worker','tanod','als_teacher']);
    $id=(int)($input['id']??0);$title=trim((string)($input['title']??''));$body=trim((string)($input['body']??''));$starts=trim((string)($input['starts_at']??''));$ends=trim((string)($input['ends_at']??''));$location=trim((string)($input['location']??''));
    $validDate=fn($v)=>$v!==''&&preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/',$v)&&strtotime($v)!==false;
    if(mb_strlen($title)<3||mb_strlen($title)>180||mb_strlen($body)<3||!$validDate($starts)||($ends!==''&&!$validDate($ends))||mb_strlen($location)>180)json_response(['error'=>'Enter a title, details, and valid start and end times.'],422);
    $startSql=str_replace('T',' ',$starts).':00';$endSql=$ends!==''?str_replace('T',' ',$ends).':00':null;
    if($id){$stmt=$pdo->prepare('SELECT author_id FROM announcements WHERE id=?');$stmt->execute([$id]);$owner=$stmt->fetchColumn();if($owner===false)json_response(['error'=>'Announcement not found.'],404);if((int)$owner!==(int)$user['id']&&$user['role']!=='admin')json_response(['error'=>'Only the author or System Admin can edit this announcement.'],403);$stmt=$pdo->prepare('UPDATE announcements SET title=?,body=?,starts_at=?,ends_at=?,location=? WHERE id=?');$stmt->execute([$title,$body,$startSql,$endSql,$location?:null,$id]);$event='announcement_updated';}
    else{$stmt=$pdo->prepare('INSERT INTO announcements(title,body,starts_at,ends_at,location,author_id,author_role) VALUES(?,?,?,?,?,?,?)');$stmt->execute([$title,$body,$startSql,$endSql,$location?:null,$user['id'],$user['role']]);$id=(int)$pdo->lastInsertId();$event='announcement_created';}
    audit($pdo,$event,'announcement',$id,['title'=>$title]);json_response(['ok'=>true,'id'=>$id]);
}
if ($action === 'announcement_cancel') {
    $user=require_roles(['admin','captain','secretary','health_worker','tanod','als_teacher']);$id=(int)($input['id']??0);$stmt=$pdo->prepare('SELECT author_id FROM announcements WHERE id=?');$stmt->execute([$id]);$owner=$stmt->fetchColumn();if($owner===false)json_response(['error'=>'Announcement not found.'],404);if((int)$owner!==(int)$user['id']&&$user['role']!=='admin')json_response(['error'=>'Only the author or System Admin can cancel this announcement.'],403);$pdo->prepare("UPDATE announcements SET status='Cancelled' WHERE id=?")->execute([$id]);audit($pdo,'announcement_cancelled','announcement',$id);json_response(['ok'=>true]);
}
if ($action === 'metrics') {
    require_roles(['admin','captain','secretary','health_worker','tanod']);
    $scalar = fn(string $sql) => (int)$pdo->query($sql)->fetchColumn();
    $month = date('Y-m');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE DATE_FORMAT(created_at,'%Y-%m')=? AND status<>'Cancelled'"); $stmt->execute([$month]); $docs=(int)$stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(fee),0) FROM documents WHERE DATE_FORMAT(created_at,'%Y-%m')=? AND status<>'Cancelled'"); $stmt->execute([$month]); $revenue=(float)$stmt->fetchColumn();
    $privateOverview = $user['role'] !== 'health_worker';
    $recent = $privateOverview ? $pdo->query("SELECT id,resident_code,first_name,last_name,purok,created_at FROM residents ORDER BY created_at DESC LIMIT 5")->fetchAll() : [];
    $puroks = $pdo->query("SELECT COALESCE(purok,'Unassigned') label,COUNT(*) value FROM residents GROUP BY purok ORDER BY value DESC LIMIT 6")->fetchAll();
    json_response(['residents'=>$scalar("SELECT COUNT(*) FROM residents WHERE status='Active'"),'households'=>$scalar("SELECT COUNT(DISTINCT household_code) FROM residents WHERE household_code IS NOT NULL AND household_code<>''"),'open_cases'=>$scalar("SELECT COUNT(*) FROM cases WHERE status IN ('Filed','For mediation','Scheduled')"),'displaced'=>$scalar('SELECT COALESCE(SUM(family_size),0) FROM displacements WHERE returned_at IS NULL'),'documents_month'=>$docs,'revenue_month'=>$revenue,'shelter_capacity'=>$pdo->query('SELECT COALESCE(SUM(capacity),0) capacity,COALESCE(SUM(occupied),0) occupied FROM shelters')->fetch(),'recent_residents'=>$recent,'puroks'=>$privateOverview?$puroks:[],'upcoming'=>$privateOverview?$pdo->query("SELECT h.id,h.hearing_at,h.hearing_type,h.venue,c.case_no,c.complainant,c.respondent FROM hearings h JOIN cases c ON c.id=h.case_id WHERE h.hearing_at>=NOW() AND h.status='Scheduled' ORDER BY h.hearing_at LIMIT 5")->fetchAll():[],'low_stock'=>$scalar('SELECT COUNT(*) FROM medicines WHERE quantity<=reorder_level')]);
}
if ($action === 'shelters') { require_roles(['admin','captain','secretary','tanod']); json_response(['items'=>$pdo->query('SELECT * FROM shelters ORDER BY name')->fetchAll()]); }
if ($action === 'audit') { require_roles(['admin','captain']); json_response(['items'=>$pdo->query('SELECT id,username,action,entity,entity_id,created_at FROM audit_logs ORDER BY id DESC LIMIT 100')->fetchAll()]); }
if ($action === 'address_history') { require_roles(['admin','captain','secretary','tanod']); $stmt=$pdo->prepare('SELECT address,purok,moved_in,moved_out,created_at FROM address_history WHERE resident_id=? ORDER BY id DESC');$stmt->execute([(int)($_GET['resident_id']??0)]);json_response(['items'=>$stmt->fetchAll()]); }
if ($action === 'staff') { require_roles(['admin']); json_response(['items'=>$pdo->query('SELECT u.id,u.name,u.username,u.email,u.phone,u.role,u.active,u.created_at,r.resident_code FROM users u LEFT JOIN residents r ON r.user_id=u.id ORDER BY u.name')->fetchAll()]); }
if ($action === 'staff_save') {
    require_roles(['admin']);
    $id=(int)($input['id']??0); $name=trim((string)($input['name']??'')); $username=trim((string)($input['username']??'')); $email=strtolower(trim((string)($input['email']??'')));$phone=trim((string)($input['phone']??''));$role=(string)($input['role']??''); $password=(string)($input['password']??'');
    if(mb_strlen($name)<2 || mb_strlen($name)>255 || !preg_match('/^[a-zA-Z0-9._-]{3,50}$/',$username) || ($email!==''&&(!is_gmail_address($email)||mb_strlen($email)>160)) || ($phone!==''&&!preg_match('/^[0-9]{7,15}$/',$phone)) || !in_array($role,['admin','captain','secretary','health_worker','tanod','als_teacher','resident'],true) || (!$id && !password_meets_policy($password)) || ($password!=='' && !password_meets_policy($password))) json_response(['error'=>'Enter a valid username, @gmail.com email, 7–15 digit phone number, role, and a password with 8+ characters, an uppercase letter, a number, and a symbol.'],422);
    if($id===(int)$user['id']&&$role!=='admin')json_response(['error'=>'You cannot remove your own System Admin role.'],422);
    if(!$id&&$role==='resident')json_response(['error'=>'Create Barangay Resident accounts through the public registration page so their resident profile is recorded.'],422);
    if($id&&$role==='resident'){$stmt=$pdo->prepare('SELECT COUNT(*) FROM residents WHERE user_id=?');$stmt->execute([$id]);if(!(int)$stmt->fetchColumn())json_response(['error'=>'This account has no resident profile. Ask the person to register as a resident first.'],422);}
    try {
        if($id) { if($password!==''){ $stmt=$pdo->prepare('UPDATE users SET name=?,username=?,email=?,phone=?,role=?,password_hash=? WHERE id=?');$stmt->execute([$name,$username,$email?:null,$phone?:null,$role,password_hash($password,PASSWORD_DEFAULT),$id]); } else { $stmt=$pdo->prepare('UPDATE users SET name=?,username=?,email=?,phone=?,role=? WHERE id=?');$stmt->execute([$name,$username,$email?:null,$phone?:null,$role,$id]); } }
        else { $stmt=$pdo->prepare('INSERT INTO users(name,username,email,phone,role,password_hash) VALUES(?,?,?,?,?,?)');$stmt->execute([$name,$username,$email?:null,$phone?:null,$role,password_hash($password,PASSWORD_DEFAULT)]);$id=(int)$pdo->lastInsertId(); }
        $sync=$pdo->prepare('UPDATE residents SET email=?,phone=? WHERE user_id=?');$sync->execute([$email?:null,$phone?:null,$id]);
        audit($pdo,'access_updated','user',$id,['role'=>$role]); json_response(['ok'=>true,'id'=>$id]);
    } catch(PDOException $e) { if((string)$e->getCode()==='23000') json_response(['error'=>'That username is already in use.'],409); throw $e; }
}
if ($action === 'staff_status') {
    require_roles(['admin']); $id=(int)($input['id']??0); $active=(int)($input['active']??0); if($id===(int)$user['id'] && !$active) json_response(['error'=>'You cannot deactivate your own account.'],422);
    $stmt=$pdo->prepare('UPDATE users SET active=? WHERE id=?');$stmt->execute([$active?1:0,$id]);audit($pdo,$active?'activated':'deactivated','user',$id);json_response(['ok'=>true]);
}
if ($action === 'list') {
    $module=(string)($_GET['module'] ?? ''); if (!isset($specs[$module])) json_response(['error'=>'Unknown module.'],404);
    require_roles($specs[$module]['roles']); $spec=$specs[$module]; $q=trim((string)($_GET['q'] ?? ''));
    $columns='*'; $order='id DESC';
    if ($module==='residents') $order='last_name,first_name';
    if ($module==='documents') $order='created_at DESC';
    if ($module==='cases') $order='created_at DESC';
    if ($module==='hearings') { $columns='hearings.*,cases.case_no,cases.complainant,cases.respondent'; $order='hearings.hearing_at DESC'; }
    if ($module==='relief') $order='distributed_at DESC';
    if ($module==='health') $order='service_date DESC';
    if ($module==='aid') $order='assistance_date DESC';
    $sql='SELECT '.$columns.' FROM '.$spec['table'];
    if ($module==='hearings') $sql.=' JOIN cases ON cases.id=hearings.case_id';
    $args=[];
    if ($q!=='') { $terms=$spec['search']; if($module==='hearings') $terms=array_map(fn($c)=>'hearings.'.$c,$terms); $sql.=' WHERE (' . implode(' OR ',array_map(fn($c)=>$c.' LIKE ?', $terms)) . ')'; foreach($terms as $_) $args[]='%'.$q.'%'; }
    $sql.=' ORDER BY '.$order.' LIMIT 500'; $stmt=$pdo->prepare($sql); $stmt->execute($args); json_response(['items'=>$stmt->fetchAll()]);
}
if ($action === 'save') {
    $module=(string)($input['module'] ?? ''); if (!isset($specs[$module])) json_response(['error'=>'Unknown module.'],404);
    $spec=$specs[$module]; require_roles($spec['roles']); $record=$input['record'] ?? []; if (!is_array($record)) json_response(['error'=>'Invalid record.'],422);
    $id=(int)($record['id'] ?? 0); $data=[]; $defaults=['gender','voter_status','status','displaced_at','service_type','quantity','unit','fee','hearing_type','amount','reorder_level']; foreach($spec['fields'] as $field) if(array_key_exists($field,$record)) { if($record[$field]==='' && in_array($field,$defaults,true)) continue; $data[$field]=$record[$field]===''?null:$record[$field]; }
    if($module==='residents' && isset($_FILES['photo']) && $_FILES['photo']['error']!==UPLOAD_ERR_NO_FILE) {
        $file=$_FILES['photo']; if($file['error']!==UPLOAD_ERR_OK || $file['size']>5*1024*1024) json_response(['error'=>'Choose an image under 5 MB.'],422);
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']); $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if(!isset($extensions[$mime])) json_response(['error'=>'Profile photo must be a JPG, PNG, or WebP image.'],422);
        $dir=__DIR__.'/uploads'; if(!is_dir($dir) && !mkdir($dir,0750,true)) json_response(['error'=>'Photo storage is unavailable.'],500);
        $filename=bin2hex(random_bytes(16)).'.'.$extensions[$mime]; if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$filename)) json_response(['error'=>'Could not save the profile photo.'],500);
        $data['photo_path']='uploads/'.$filename;
    }
    if($module==='residents' && isset($data['sectors']) && is_array($data['sectors'])) $data['sectors']=json_encode(array_values($data['sectors']));
    if($module==='residents' && !$id && empty($data['resident_code'])) $data['resident_code']='SJ-'.date('y').'-'.strtoupper(bin2hex(random_bytes(3)));
    if($module==='documents') {
        if(!$id) { $data['document_no']='SJ-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(2))); $data['verification_token']=bin2hex(random_bytes(32)); $data['issued_by']=$user['id']; }
        if(($data['status'] ?? '')==='Released' && !$id) $data['issued_at']=date('Y-m-d H:i:s');
        if(($data['status'] ?? '')==='Released' && $id) $data['issued_at']=date('Y-m-d H:i:s');
    }
    if($module==='cases' && !$id) { $data['case_no']='KP-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(3))); $data['created_by']=$user['id']; }
    if($module==='cases' && ($data['status'] ?? '')==='Referred' && empty($data['cfa_no'])) $data['cfa_no']='CFA-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(2)));
    if($module==='relief' && !$id) { $data['reference_no']='RL-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(2))); $data['distributed_by']=$user['id']; }
    if($module==='aid' && !$id) { $data['reference_no']='FA-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(2))); $data['created_by']=$user['id']; }
    if($module==='cases' && $id && ($data['status'] ?? '')==='Referred' && empty($data['cfa_no'])) $data['cfa_no']='CFA-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(2)));
    if(!$data) json_response(['error'=>'No fields to save.'],422);
    $previousAddress = null;
    if($module==='residents' && $id && array_key_exists('address',$data)) { $stmt=$pdo->prepare('SELECT address,purok FROM residents WHERE id=?');$stmt->execute([$id]);$previousAddress=$stmt->fetch(); }
    $previousDisplacement = null;
    if($module==='displacements' && $id) { $stmt=$pdo->prepare('SELECT shelter_id,family_size,returned_at FROM displacements WHERE id=?');$stmt->execute([$id]);$previousDisplacement=$stmt->fetch(); }
    try {
        if($id) { $set=implode(',',array_map(fn($k)=>$k.'=?',array_keys($data))); $stmt=$pdo->prepare('UPDATE '.$spec['table'].' SET '.$set.' WHERE id=?'); $stmt->execute([...array_values($data),$id]); $savedId=$id; $verb='updated'; }
        else { $cols=implode(',',array_keys($data)); $marks=implode(',',array_fill(0,count($data),'?')); $stmt=$pdo->prepare('INSERT INTO '.$spec['table'].' ('.$cols.') VALUES ('.$marks.')'); $stmt->execute(array_values($data)); $savedId=(int)$pdo->lastInsertId(); $verb='created'; }
    } catch(PDOException $e) { if((string)$e->getCode()==='23000'&&$module==='relief') json_response(['error'=>'This household has already received this package type.'],409); if((string)$e->getCode()==='23000') json_response(['error'=>'This record conflicts with an existing unique value. Check the reference or ID and try again.'],409); throw $e; }
    if($module==='residents' && !$id && !empty($data['address'])) { $stmt=$pdo->prepare('INSERT INTO address_history(resident_id,address,purok,recorded_by) VALUES(?,?,?,?)'); $stmt->execute([$savedId,$data['address'],$data['purok']??null,$user['id']]); }
    if($module==='residents' && $id && $previousAddress && ($previousAddress['address']!==($data['address']??$previousAddress['address']) || $previousAddress['purok']!==($data['purok']??$previousAddress['purok']))) {
        $stmt=$pdo->prepare('UPDATE address_history SET moved_out=CURDATE() WHERE resident_id=? AND moved_out IS NULL ORDER BY id DESC LIMIT 1');$stmt->execute([$id]);
        $stmt=$pdo->prepare('INSERT INTO address_history(resident_id,address,purok,recorded_by) VALUES(?,?,?,?)');$stmt->execute([$id,$data['address']??$previousAddress['address'],$data['purok']??$previousAddress['purok'],$user['id']]);
    }
    if($module==='displacements') {
        if($previousDisplacement && empty($previousDisplacement['returned_at']) && !empty($previousDisplacement['shelter_id'])) { $sid=(int)$previousDisplacement['shelter_id'];$stmt=$pdo->prepare('UPDATE shelters SET occupied=GREATEST(0,occupied-?) WHERE id=?');$stmt->execute([(int)$previousDisplacement['family_size'],$sid]);$stmt=$pdo->prepare("UPDATE shelters SET status=IF(status='Closed','Closed',IF(occupied<capacity,'Open','Full')) WHERE id=?");$stmt->execute([$sid]); }
        $newShelter=(int)($data['shelter_id']??($previousDisplacement['shelter_id']??0)); $newSize=(int)($data['family_size']??($previousDisplacement['family_size']??0)); $returned=$data['returned_at']??($previousDisplacement['returned_at']??null);
        if(!$returned && $newShelter>0 && $newSize>0) { $stmt=$pdo->prepare('UPDATE shelters SET occupied=occupied+? WHERE id=?');$stmt->execute([$newSize,$newShelter]);$stmt=$pdo->prepare("UPDATE shelters SET status=IF(status='Closed','Closed',IF(occupied>=capacity,'Full','Open')) WHERE id=?");$stmt->execute([$newShelter]); }
    }
    audit($pdo,$verb,$module,$savedId,['fields'=>array_keys($data)]); json_response(['ok'=>true,'id'=>$savedId]);
}
if ($action === 'delete') {
    $module=(string)($input['module'] ?? ''); if(!isset($specs[$module])) json_response(['error'=>'Unknown module.'],404); require_roles($specs[$module]['roles']);
    if($module==='shelters') { $id=(int)($input['id']??0);$stmt=$pdo->prepare('SELECT COUNT(*) FROM displacements WHERE shelter_id=? AND returned_at IS NULL');$stmt->execute([$id]);if((int)$stmt->fetchColumn()>0)json_response(['error'=>'This shelter has active families checked in. Check them out or move them before removing it.'],409); }
    if($module==='documents' && in_array($user['role'],['secretary','captain','admin'],true)) { $stmt=$pdo->prepare("UPDATE documents SET status='Cancelled' WHERE id=?"); $stmt->execute([(int)($input['id']??0)]); }
    else { $id=(int)($input['id']??0);$displacement=null;if($module==='displacements'){$stmt=$pdo->prepare('SELECT shelter_id,family_size,returned_at FROM displacements WHERE id=?');$stmt->execute([$id]);$displacement=$stmt->fetch();} $stmt=$pdo->prepare('DELETE FROM '.$specs[$module]['table'].' WHERE id=?'); $stmt->execute([$id]);if($module==='displacements'&&$displacement&&empty($displacement['returned_at'])&&!empty($displacement['shelter_id'])){$sid=(int)$displacement['shelter_id'];$stmt=$pdo->prepare('UPDATE shelters SET occupied=GREATEST(0,occupied-?) WHERE id=?');$stmt->execute([(int)$displacement['family_size'],$sid]);$stmt=$pdo->prepare("UPDATE shelters SET status=IF(status='Closed','Closed',IF(occupied<capacity,'Open','Full')) WHERE id=?");$stmt->execute([$sid]);} }
    audit($pdo,$module==='documents'?'cancelled':'deleted',$module,(int)($input['id']??0)); json_response(['ok'=>true]);
}
json_response(['error'=>'Unknown action.'],404);
