<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth_check.php';
require_once __DIR__.'/../config/content.php';
require_once __DIR__.'/../config/media.php';
$module = is_string($_GET['module'] ?? null) ? $_GET['module'] : 'students';
$definition = content_modules()[$module] ?? null;
if (!$definition) { http_response_code(404); exit('Content section not found.'); }
require_admin_permission($definition['permission']);
$page_title = $definition['title']; $active_page='content-'.$module;
$table=$definition['table']; $id=(int)($_GET['edit'] ?? $_POST['id'] ?? 0);
$record=[]; $error='';
if ($id) { $stmt=$pdo->prepare("SELECT * FROM $table WHERE id=:id"); $stmt->execute([':id'=>$id]); $record=$stmt->fetch(); if (!$record) { http_response_code(404); exit('Record not found.'); } }
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        $action=$_POST['action'] ?? '';
        if ($action==='delete' && $id) {
            $pdo->prepare("DELETE FROM $table WHERE id=:id")->execute([':id'=>$id]);
        } elseif ($action==='visibility' && $id) {
            $pdo->prepare("UPDATE $table SET status=:status WHERE id=:id")->execute([':status'=>(int)!$record['status'],':id'=>$id]);
        } elseif ($action==='save') {
            $values=validate_content($definition,$_POST);
            $photo = $record[$definition['image']] ?? null;
            if (!empty($_POST['media_id'])) {
                $asset=$pdo->prepare('SELECT filename FROM media_assets WHERE id=:id'); $asset->execute([':id'=>(int)$_POST['media_id']]);
                $photo=$asset->fetchColumn(); if (!$photo) throw new InvalidArgumentException('Choose an image from the media library.');
            }
            if (($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE) {
                $photo=save_media($pdo,$_FILES['photo'],(string)($_POST['alt_text'] ?? ''),['students'=>'Students','colleges'=>'Colleges','gallery'=>'Events'][$module]);
            }
            if (isset($_POST['remove_photo'])) $photo=null;
            $values[$definition['image']]=$photo;
            $keys=array_keys($values);
            if ($id) {
                $sets=implode(',',array_map(fn($key)=>"$key=:$key",$keys));
                $values['id']=$id; $stmt=$pdo->prepare("UPDATE $table SET $sets WHERE id=:id");
            } else {
                $stmt=$pdo->prepare("INSERT INTO $table (".implode(',',$keys).') VALUES (:'.implode(',:',$keys).')');
            }
            $stmt->execute($values);
            $id=$id ?: (int)$pdo->lastInsertId();
        } else throw new InvalidArgumentException('Choose a valid action.');
        log_admin_activity($pdo,'CONTENT_'.strtoupper($action),$page_title,$table,$id);
        set_flash_message('success','Content updated successfully.');
        header('Location: '.BASE_URL.'/admin/content.php?module='.$module); exit;
    } catch (PDOException $e) { error_log($e->getMessage()); $error='Unable to save right now. Please try again.'; }
      catch (InvalidArgumentException|RuntimeException $e) { $error=safe_error_message($e); }
      catch (Throwable $e) { error_log($e->getMessage()); $error='Unable to save right now. Your entered text is preserved below.'; }
    $record=array_merge($record ?: [],array_intersect_key($_POST,$definition['fields']),['status'=>isset($_POST['status'])?1:0]);
}
$stmt=$pdo->prepare("SELECT * FROM $table ORDER BY id DESC");$stmt->execute();$records=$stmt->fetchAll();
$stmt=$pdo->prepare('SELECT id,filename,alt_text FROM media_assets ORDER BY id DESC LIMIT 200');$stmt->execute();$media=$stmt->fetchAll();
require __DIR__.'/../includes/admin-content-header.php';
?>
<div class="row g-4"><div class="col-xl-5">
<section class="card p-4"><h2 class="h5 mb-3"><?= $id ? 'Edit content' : 'Create a new story' ?></h2>
<?php if($error): ?><div class="alert alert-danger" role="alert"><?= content_escape($error) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" data-media-form>
<?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="save">
<?php foreach($definition['fields'] as $key=>[$label,$type,$limit]): $value=$record[$key] ?? ($type==='number'?'0':($type==='year'?date('Y'):'')); ?>
<div class="form-floating mb-3">
<?php if($type==='textarea'): ?><textarea class="form-control" id="field-<?= $key ?>" name="<?= $key ?>" placeholder=" " maxlength="<?= $limit ?>" style="height:130px" required><?= content_escape($value) ?></textarea>
<?php elseif($type==='select'): ?><select class="form-select" name="<?= $key ?>" id="field-<?= $key ?>" required><option value="">Select</option><?php foreach($limit as $option): ?><option <?= $value===$option?'selected':'' ?>><?= content_escape($option) ?></option><?php endforeach; ?></select>
<?php else: ?><input class="form-control" id="field-<?= $key ?>" name="<?= $key ?>" type="<?= $type==='year'?'number':$type ?>" value="<?= content_escape($value) ?>" placeholder=" " <?= $type!=='url'?'required':'' ?> <?= in_array($type,['text','url'])?'maxlength="'.$limit.'"':'' ?>><?php endif; ?>
<label for="field-<?= $key ?>"><?= content_escape($label) ?></label></div>
<?php endforeach; ?>
<label for="media-id" class="form-label">Choose an existing image</label><select id="media-id" name="media_id" class="form-select mb-3"><option value="">Keep current image / none</option><?php foreach($media as $asset): ?><option value="<?= (int)$asset['id'] ?>"><?= content_escape($asset['alt_text']) ?></option><?php endforeach; ?></select>
<label class="form-label" for="photo">Or upload a photo</label><input class="form-control mb-2" id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-optimize-image><p class="small text-muted">JPG, PNG or WebP. Up to 5 MB and 24 megapixels. Images are resized to 1600px.</p>
<div class="form-floating mb-3"><input class="form-control" id="alt-text" name="alt_text" maxlength="200" placeholder=" "><label for="alt-text">Describe the new photo (alt text)</label></div>
<?php if(!empty($record[$definition['image']])): ?><div class="community-photo mb-2"><?= content_image($pdo,$record[$definition['image']],$record[$definition['name']]??'') ?></div><label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remove_photo"> Remove current photo</label><?php endif; ?>
<label class="form-check mb-4"><input class="form-check-input" type="checkbox" name="status" <?= !empty($record['status'])?'checked':'' ?>> Visible on the public website</label>
<p class="small text-muted">Publish only approved stories and photos with permission from the people or colleges featured.</p>
<div class="d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Save content</button><?php if($id): ?><a class="btn btn-light" href="?module=<?= $module ?>">Cancel</a><?php endif; ?></div>
</form></section></div>
<div class="col-xl-7"><section class="card p-4"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Your content</h2><span class="badge text-bg-light"><?= count($records) ?> entries</span></div>
<div class="table-responsive"><table class="table" data-enhance-table><thead><tr><th>Title</th><th>Visibility</th><th>Actions</th></tr></thead><tbody>
<?php foreach($records as $row): ?><tr><td><?= content_escape($row[$definition['name']]) ?></td><td><span class="badge <?= $row['status']?'text-bg-success':'text-bg-secondary' ?>"><?= $row['status']?'Published':'Draft' ?></span></td><td><div class="d-flex flex-wrap gap-1"><a class="btn btn-sm btn-light" href="?module=<?= $module ?>&edit=<?= (int)$row['id'] ?>">Edit</a><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-sm btn-outline-secondary" name="action" value="visibility"><?= $row['status']?'Hide':'Publish' ?></button></form><form method="post" onsubmit="return confirm('Delete this content entry? Its media stays in the library.');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-sm btn-outline-danger" name="action" value="delete">Delete</button></form></div></td></tr><?php endforeach; ?>
</tbody></table></div><?php if(!$records): ?><div class="empty-state"><i class="bi bi-journal-plus"></i><h3 class="h6">A new chapter starts here</h3><p>Add your first approved entry. Drafts stay private until you publish.</p></div><?php endif; ?>
</section></div></div>
<?php require __DIR__.'/../includes/admin-content-footer.php'; ?>
