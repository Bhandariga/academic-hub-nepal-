<?php
require_once __DIR__.'/../config/db.php'; require_once __DIR__.'/../config/auth_check.php';
require_once __DIR__.'/../config/content.php'; require_once __DIR__.'/../config/media.php';
require_admin(); if (!can_manage_media()) { http_response_code(403); exit('Media permission required.'); }
$page_title='Media Library';$active_page='media';$error='';
if (($_SERVER['REQUEST_METHOD']??'')==='POST') {
    try {
        $alt=trim((string)($_POST['alt_text']??''));
        if (($_POST['action']??'')==='update') {
            if ($alt==='' || mb_strlen($alt)>200) throw new InvalidArgumentException('Enter a description up to 200 characters.');
            $stmt=$pdo->prepare('UPDATE media_assets SET alt_text=:alt WHERE id=:id');$stmt->execute([':alt'=>$alt,':id'=>(int)$_POST['id']]);
        } else save_media($pdo,$_FILES['photo']??[],$alt,(string)($_POST['category']??''));
        log_admin_activity($pdo,'MEDIA_SAVE','Updated media library');
        set_flash_message('success','Image saved to your media library.');header('Location: '.BASE_URL.'/admin/media.php');exit;
    } catch (PDOException $e) { error_log($e->getMessage());$error='Unable to save the image record.'; }
      catch (InvalidArgumentException|RuntimeException $e) { $error=safe_error_message($e); }
}
$stmt=$pdo->prepare('SELECT * FROM media_assets ORDER BY id DESC LIMIT 200');$stmt->execute();$assets=$stmt->fetchAll();
require __DIR__.'/../includes/admin-content-header.php'; ?>
<section class="card p-4 mb-4"><h2 class="h5">One library for every story</h2><p class="text-muted">Upload student, college, team, event and resource-cover photography. Reuse images across content without uploading duplicates.</p>
<?php if($error): ?><div class="alert alert-danger" role="alert"><?= content_escape($error) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" data-media-form class="row g-3 align-items-end"><?= csrf_field() ?>
<div class="col-lg-4"><label for="photo" class="form-label">Image • JPG, PNG, WebP</label><input class="form-control" type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/webp" data-optimize-image required></div>
<div class="col-lg-3"><label for="alt" class="form-label">Alt text</label><input class="form-control" id="alt" name="alt_text" required maxlength="200" placeholder="Describe what the photo shows"></div>
<div class="col-lg-3"><label for="category" class="form-label">Category</label><select class="form-select" name="category" id="category"><?php foreach(['Students','Colleges','Team','Events','Resource covers'] as $category): ?><option><?= $category ?></option><?php endforeach; ?></select></div>
<div class="col-lg-2"><button class="btn btn-primary w-100" type="submit">Upload image</button></div>
<p class="small text-muted mb-0">Maximum 5 MB / 24 megapixels. Automatic resize to 1600px and WebP compression in supported browsers.</p>
</form></section>
<div class="row g-4"><?php foreach($assets as $asset): ?><div class="col-md-6 col-xl-4"><article class="card overflow-hidden h-100"><div class="community-photo"><?= content_image($pdo,$asset['filename'],$asset['alt_text']) ?></div><div class="p-3"><span class="eyebrow"><?= content_escape($asset['category']) ?></span><p class="small text-muted"><?= (int)$asset['width'] ?> × <?= (int)$asset['height'] ?> · <?= number_format($asset['file_size']/1024) ?> KB</p><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$asset['id'] ?>"><input type="hidden" name="action" value="update"><label class="form-label small" for="alt-<?= (int)$asset['id'] ?>">Alt text</label><input class="form-control mb-2" id="alt-<?= (int)$asset['id'] ?>" name="alt_text" value="<?= content_escape($asset['alt_text']) ?>" maxlength="200" required><button class="btn btn-sm btn-outline-primary">Save description</button></form></div></article></div><?php endforeach; ?></div>
<?php if(!$assets): ?><div class="empty-state card"><i class="bi bi-images"></i><h2 class="h5">Your media library is ready</h2><p>Upload the first photo to bring your community’s stories to life.</p></div><?php endif; ?>
<?php require __DIR__.'/../includes/admin-content-footer.php'; ?>
