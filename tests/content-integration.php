<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
putenv('ALLOW_DB_SETUP=0');require __DIR__.'/../config/db.php';require __DIR__.'/../config/content.php';
$base=getenv('TEST_BASE_URL')?:'http://localhost/Academic%20Hub/Academic%20Hub';
$tag='QA-'.bin2hex(random_bytes(6));$password=bin2hex(random_bytes(20));$uid=0;$teamId=0;$contentIds=[];$assetFiles=[];
$jar=__DIR__.'/../logs/'.$tag.'.cookies';$png=__DIR__.'/../logs/'.$tag.'.png';$invalid=__DIR__.'/../logs/'.$tag.'-bad.png';
function expect_check(bool $condition,string $label):void{if(!$condition)throw new RuntimeException($label);echo "PASS: $label\n";}
function request_page(string $path,?array $data=null):array{
 global $base,$jar;$c=curl_init($base.'/'.$path);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_COOKIEJAR=>$jar,CURLOPT_COOKIEFILE=>$jar,CURLOPT_TIMEOUT=>15]);
 if($data!==null)curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$data]);
 $body=curl_exec($c);$code=curl_getinfo($c,CURLINFO_HTTP_CODE);$url=curl_getinfo($c,CURLINFO_EFFECTIVE_URL);curl_close($c);
 if(!is_string($body))throw new RuntimeException('HTTP request failed');
 expect_check(!preg_match('/Fatal error:|Warning:|Parse error:/',$body),'No PHP error: '.$path);
 return [$code,$body,$url];
}
function token_from(string $body):string{if(!preg_match('/name="csrf_token"\s+value="([^"]+)"/',$body,$m))throw new RuntimeException('Missing CSRF token');return $m[1];}
try {
 $insert=$pdo->prepare("INSERT INTO users(name,email,password,role,permissions,is_active,campus) VALUES(:name,:email,:password,'superadmin','[\"all\"]',1,'QA test campus')");
 $email=strtolower($tag).'@example.invalid';$insert->execute([':name'=>$tag,':email'=>$email,':password'=>password_hash($password,PASSWORD_DEFAULT)]);$uid=(int)$pdo->lastInsertId();
 [, $login]=request_page('admin/admin-login.php');
 [$status,$dashboard,$url]=request_page('admin/admin-login.php',['csrf_token'=>token_from($login),'email'=>$email,'password'=>$password]);
 expect_check(str_contains($url,'admin/dashboard.php'),'Real password login reaches dashboard');
 foreach(content_modules() as $module=>$definition){
  [, $page]=request_page('admin/content.php?module='.$module);$csrf=token_from($page);
  $values=['csrf_token'=>$csrf,'action'=>'save','id'=>0];
  foreach($definition['fields'] as $key=>[$label,$type,$limit])$values[$key]=$type==='select'?$limit[0]:($type==='year'?'2026':($type==='number'?'1':($type==='url'?'https://example.org':$tag)));
  request_page('admin/content.php?module='.$module,$values);
  $stmt=$pdo->prepare("SELECT * FROM {$definition['table']} WHERE {$definition['name']}=:name");$stmt->execute([':name'=>$tag]);$row=$stmt->fetch();
  expect_check((bool)$row,'Create '.$module.' draft');$id=(int)$row['id'];$contentIds[$module]=$id;
  expect_check(!(int)$row['status'],'New content remains unpublished');
  expect_check(!in_array($id,array_column(content_rows($pdo,$module),'id')),'Draft excluded from public query');
  $values['id']=$id;$values['status']='1';$values[$definition['name']]=$tag.' edited';
  request_page('admin/content.php?module='.$module,$values);
  expect_check(in_array($id,array_column(content_rows($pdo,$module),'id')),'Published '.$module.' visible');
  request_page('admin/content.php?module='.$module,['csrf_token'=>$csrf,'id'=>$id,'action'=>'visibility']);
  expect_check(!in_array($id,array_column(content_rows($pdo,$module),'id')),'Hide '.$module.' works');
  [$bad]=request_page('admin/content.php?module='.$module,['action'=>'save']);expect_check($bad===403,'Missing CSRF rejected for '.$module);
 }
 [, $teamPage]=request_page('admin/manage-team.php');$csrf=token_from($teamPage);
 $member=['csrf_token'=>$csrf,'action'=>'create_member','name'=>$tag,'role_title'=>'QA Researcher','category'=>'Research Consultants','department'=>'QA','bio'=>'Test profile','display_order'=>'999','is_active'=>'1'];
 request_page('admin/manage-team.php',$member);
 $stmt=$pdo->prepare('SELECT id FROM team_members WHERE name=:name');$stmt->execute([':name'=>$tag]);$teamId=(int)$stmt->fetchColumn();expect_check($teamId>0,'Create team member');
 $member['action']='update_member';$member['id']=$teamId;$member['category']='Technical Team';$member['role_title']='QA Updated';
 request_page('admin/manage-team.php',$member);
 $stmt=$pdo->prepare('SELECT category,role_title FROM team_members WHERE id=:id');$stmt->execute([':id'=>$teamId]);$updated=$stmt->fetch();expect_check($updated && $updated['category']==='Technical Team' && $updated['role_title']==='QA Updated','Team edit preserves row and updates category');
 [, $teamPublic]=request_page('team.php');expect_check(str_contains($teamPublic,'team-profile-'.$teamId),'Published team profile modal rendered');
 request_page('admin/manage-team.php',['csrf_token'=>$csrf,'action'=>'toggle_status','id'=>$teamId,'status'=>'0']);
 [, $teamPublic]=request_page('team.php');expect_check(!str_contains($teamPublic,'team-profile-'.$teamId),'Inactive team profile hidden');
 file_put_contents($png,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
 file_put_contents($invalid,'<?php echo "not an image";');
 [, $mediaPage]=request_page('admin/media.php');$csrf=token_from($mediaPage);
 request_page('admin/media.php',['csrf_token'=>$csrf,'alt_text'=>$tag,'category'=>'Events','photo'=>new CURLFile($png,'image/png','photo.png')]);
 $stmt=$pdo->prepare('SELECT filename FROM media_assets WHERE alt_text=:alt');$stmt->execute([':alt'=>$tag]);$assetFiles=$stmt->fetchAll(PDO::FETCH_COLUMN);expect_check(count($assetFiles)===1,'Validated image stored in central library');
 request_page('admin/media.php',['csrf_token'=>$csrf,'alt_text'=>$tag.' invalid','category'=>'Events','photo'=>new CURLFile($invalid,'image/png','photo.png')]);
 $stmt=$pdo->prepare('SELECT COUNT(*) FROM media_assets WHERE alt_text=:alt');$stmt->execute([':alt'=>$tag.' invalid']);expect_check((int)$stmt->fetchColumn()===0,'Disguised executable upload rejected');
 foreach($contentIds as $module=>$id){request_page('admin/content.php?module='.$module,['csrf_token'=>$csrf,'id'=>$id,'action'=>'delete']);$table=content_modules()[$module]['table'];$stmt=$pdo->prepare("SELECT COUNT(*) FROM $table WHERE id=:id");$stmt->execute([':id'=>$id]);expect_check((int)$stmt->fetchColumn()===0,'Delete '.$module.' works');}
 request_page('admin/manage-team.php',['csrf_token'=>$csrf,'action'=>'delete_member','id'=>$teamId]);$stmt=$pdo->prepare('SELECT COUNT(*) FROM team_members WHERE id=:id');$stmt->execute([':id'=>$teamId]);expect_check((int)$stmt->fetchColumn()===0,'Delete team member works');
 $pdo->prepare("UPDATE users SET role='admin',permissions='[\"manage_colleges\"]' WHERE id=:id")->execute([':id'=>$uid]);
 [$denied]=request_page('admin/content.php?module=students');expect_check($denied===403,'Revoked permission takes effect in existing session');
 [$allowed]=request_page('admin/content.php?module=colleges');expect_check($allowed===200,'Assigned content permission allowed');
 echo "Content integration checks passed.\n";
} finally {
 foreach($contentIds as $module=>$id){$table=content_modules()[$module]['table'];$pdo->prepare("DELETE FROM $table WHERE id=:id")->execute([':id'=>$id]);}
 if($teamId)$pdo->prepare('DELETE FROM team_members WHERE id=:id')->execute([':id'=>$teamId]);
 foreach($assetFiles as $file){$pdo->prepare('DELETE FROM media_assets WHERE filename=:file')->execute([':file'=>$file]);$path=__DIR__.'/../uploads/media/'.basename($file);if(is_file($path))unlink($path);}
 if($uid){$pdo->prepare('DELETE FROM admin_activity_logs WHERE admin_id=:id')->execute([':id'=>$uid]);$pdo->prepare('DELETE FROM users WHERE id=:id')->execute([':id'=>$uid]);}
 foreach([$jar,$png,$invalid] as $file)if(is_file($file))unlink($file);
 echo "Disposable fixtures cleaned up.\n";
}
