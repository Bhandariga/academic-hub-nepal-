<?php
require_once __DIR__.'/content.php'; require_once __DIR__.'/media.php';
function team_categories(): array { return ['Executive Team','Academic Advisors','Research Consultants','Technical Team']; }
function selected_team_photo(PDO $pdo,string $name,?string $current): ?string {
    $filename=null;
    if (($_FILES['photo']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE) {
        $filename=save_media($pdo,$_FILES['photo'],trim((string)($_POST['photo_alt']??''))?:$name,'Team');
    } elseif (!empty($_POST['media_id'])) {
        $stmt=$pdo->prepare('SELECT filename FROM media_assets WHERE id=:id');$stmt->execute([':id'=>(int)$_POST['media_id']]);$filename=$stmt->fetchColumn();
        if(!$filename)throw new InvalidArgumentException('Selected media could not be found.');
    }
    if(!$filename)return $current;
    $source=__DIR__.'/../uploads/media/'.basename($filename);
    $copyName='library_'.bin2hex(random_bytes(12)).'.'.pathinfo($filename,PATHINFO_EXTENSION);
    if(!copy($source,__DIR__.'/../uploads/team/'.$copyName))throw new RuntimeException('Unable to attach this photo.');
    return $copyName;
}
function team_media_fields(PDO $pdo): void {
    $stmt=$pdo->prepare("SELECT id,alt_text FROM media_assets ORDER BY id DESC LIMIT 200");$stmt->execute();
    echo '<div class="col-12"><label class="form-label small">Choose library photo<select name="media_id" class="form-select"><option value="">Keep current / use upload</option>';
    foreach($stmt->fetchAll() as $asset)echo '<option value="'.(int)$asset['id'].'">'.content_escape($asset['alt_text']).'</option>';
    echo '</select></label><label class="form-label small d-block">New photo description<input class="form-control" name="photo_alt" maxlength="200" placeholder="Defaults to member name"></label></div>';
}
