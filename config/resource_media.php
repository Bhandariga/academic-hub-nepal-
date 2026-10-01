<?php
require_once __DIR__.'/content.php';require_once __DIR__.'/media.php';
function resource_cover(PDO $pdo,string $title,?string $current): ?string {
    $file=null;
    if (($_FILES['preview_image']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE) {
        $file=save_media($pdo,$_FILES['preview_image'],trim((string)($_POST['cover_alt']??''))?:$title,'Resource covers');
    } elseif(!empty($_POST['cover_media_id'])) {
        $stmt=$pdo->prepare('SELECT filename FROM media_assets WHERE id=:id');$stmt->execute([':id'=>(int)$_POST['cover_media_id']]);$file=$stmt->fetchColumn();
        if(!$file)throw new RuntimeException('Choose an image from the media library.');
    }
    if(!$file)return $current;
    $name='cover_'.bin2hex(random_bytes(12)).'.'.pathinfo($file,PATHINFO_EXTENSION);
    if(!copy(__DIR__.'/../uploads/media/'.basename($file),PREVIEW_IMAGE_DIR.$name))throw new RuntimeException('Unable to attach resource cover.');
    return $name;
}
function resource_media_picker(PDO $pdo):void {
    $stmt=$pdo->prepare('SELECT id,alt_text FROM media_assets ORDER BY id DESC LIMIT 200');$stmt->execute();
    echo '<label class="form-label small mt-3 d-block">Or choose a library cover<select name="cover_media_id" class="form-select"><option value="">Keep current / use upload</option>';
    foreach($stmt->fetchAll() as $asset)echo '<option value="'.(int)$asset['id'].'">'.content_escape($asset['alt_text']).'</option>';
    echo '</select></label><label class="form-label small d-block">New image description<input name="cover_alt" class="form-control" maxlength="200" placeholder="Defaults to resource title"></label>';
}
