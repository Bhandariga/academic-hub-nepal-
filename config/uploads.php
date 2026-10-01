<?php
require_once __DIR__ . '/upload_scan.php';
/** Validate extension and actual content before moving an uploaded file. */
function store_validated_upload(array $file, string $directory, bool $image = false): string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new RuntimeException('The upload did not complete. Please select the file again.');
    }
    $limit = ($image ? 5 : 50) * 1024 * 1024;
    $actualSize = filesize($file['tmp_name']);
    if (!$actualSize || $actualSize > $limit) throw new RuntimeException('The file exceeds the upload size limit.');
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $types = $image ? ['jpg'=>['image/jpeg'], 'jpeg'=>['image/jpeg'], 'png'=>['image/png'], 'webp'=>['image/webp']]
        : ['pdf'=>['application/pdf'], 'doc'=>['application/msword','application/x-ole-storage','application/CDFV2'],
            'docx'=>['application/zip','application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'pptx'=>['application/zip','application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'zip'=>['application/zip','application/x-zip-compressed']];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($types[$extension]) || !in_array($mime, $types[$extension], true)) throw new RuntimeException('File content does not match an allowed format.');
    if ($image) {
        $dimensions = @getimagesize($file['tmp_name']);
        if (!$dimensions || $dimensions[0] * $dimensions[1] > 24000000) throw new RuntimeException('Use a valid image up to 24 megapixels.');
    }
    if (in_array($extension, ['docx','pptx'], true)) {
        if (!class_exists('ZipArchive')) throw new RuntimeException('Office document validation is unavailable. Please contact support.');
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) throw new RuntimeException('Invalid Office document.');
        $valid = $zip->locateName('[Content_Types].xml') !== false && $zip->locateName($extension === 'docx' ? 'word/document.xml' : 'ppt/presentation.xml') !== false;
        $zip->close();
        if (!$valid) throw new RuntimeException('Invalid Office document structure.');
    }
    scan_uploaded_content($file['tmp_name']);
    $name = bin2hex(random_bytes(20)) . '.' . $extension;
    if (!move_uploaded_file($file['tmp_name'], $directory . $name)) throw new RuntimeException('Unable to save the uploaded file.');
    return $name;
}
