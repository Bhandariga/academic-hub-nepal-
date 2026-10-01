<?php
require_once __DIR__ . '/uploads.php';
function media_permissions(): array { return ['manage_students_success','manage_colleges','manage_team','manage_gallery','manage_resources']; }
function can_manage_media(): bool {
    foreach (media_permissions() as $permission) if (admin_has_permission($permission)) return true;
    return false;
}
function save_media(PDO $pdo, array $file, string $alt, string $category): string {
    $alt = trim($alt);
    if ($alt === '' || mb_strlen($alt)>200) throw new InvalidArgumentException('Describe the image in 1–200 characters for accessibility.');
    if (!in_array($category,['Students','Colleges','Team','Events','Resource covers'],true)) throw new InvalidArgumentException('Choose a valid image category.');
    $info = isset($file['tmp_name']) ? @getimagesize($file['tmp_name']) : false;
    if (!$info || $info[0] * $info[1] > 24000000) throw new InvalidArgumentException('Use an image up to 24 megapixels.');
    $directory = __DIR__ . '/../uploads/media/';
    if (!is_dir($directory) && !mkdir($directory,0755,true)) throw new RuntimeException('Media storage is unavailable.');
    $name = store_validated_upload($file,$directory,true);
    // Browser uploads are resized before submission; GD provides defense-in-depth when available.
    if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
        $source = imagecreatefromstring(file_get_contents($directory.$name));
        if ($source) {
            $ratio = min(1,1600/max($info[0],$info[1]));
            $scaled = imagescale($source,max(1,(int)round($info[0]*$ratio)),max(1,(int)round($info[1]*$ratio)));
            if ($scaled) {
                $newName = bin2hex(random_bytes(20)).'.webp';
                if (imagewebp($scaled,$directory.$newName,82)) { unlink($directory.$name); $name=$newName; }
                imagedestroy($scaled);
            }
            imagedestroy($source);
        }
    }
    $info = getimagesize($directory.$name);
    try {
        $stmt=$pdo->prepare('INSERT INTO media_assets (filename,alt_text,category,width,height,file_size,uploaded_by) VALUES (:file,:alt,:category,:width,:height,:size,:user)');
        $stmt->execute([':file'=>$name,':alt'=>$alt,':category'=>$category,':width'=>$info[0],':height'=>$info[1],':size'=>filesize($directory.$name),':user'=>$_SESSION['user_id']]);
    } catch (Throwable $e) { unlink($directory.$name); throw $e; }
    return $name;
}
