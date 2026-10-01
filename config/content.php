<?php
/** Server-owned field definitions. Request data never supplies SQL identifiers. */
function content_modules(): array {
    return [
        'students' => ['table'=>'successful_students','title'=>'Successful Students','permission'=>'manage_students_success','image'=>'photo','name'=>'name', 'fields'=>[
            'name'=>['Student name','text',100], 'program'=>['Program','select',['BBS','BBA','MBA','MBS']],
            'campus'=>['Campus','text',150], 'achievement'=>['Achievement','text',200],
            'graduation_year'=>['Graduation year','year',4], 'story'=>['Success story','textarea',5000],
            'display_order'=>['Display order','number',10]]],
        'colleges' => ['table'=>'colleges','title'=>'Associated Colleges','permission'=>'manage_colleges','image'=>'photo','name'=>'college_name', 'fields'=>[
            'college_name'=>['College name','text',150], 'location'=>['Location','text',150],
            'university'=>['University affiliation','text',150], 'programs'=>['Programs available','text',255],
            'website'=>['Website or social link','url',255], 'description'=>['College introduction','textarea',5000]]],
        'gallery' => ['table'=>'society_gallery','title'=>'Society Gallery','permission'=>'manage_gallery','image'=>'image','name'=>'title', 'fields'=>[
            'title'=>['Title','text',150], 'category'=>['Category','select',['Campus life','Student activities','Research events','Workshops','Achievements']],
            'description'=>['Description','textarea',5000]]]
    ];
}
function content_escape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function content_url($url): string {
    return is_string($url) && filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['https','http'], true) ? $url : '';
}
function content_rows(PDO $pdo, string $module, int $limit = 100): array {
    $definition = content_modules()[$module] ?? null;
    if (!$definition) return [];
    $order = $module === 'students' ? 'display_order ASC, id DESC' : 'id DESC';
    $stmt = $pdo->prepare("SELECT * FROM {$definition['table']} WHERE status = 1 ORDER BY $order LIMIT :limit");
    $stmt->bindValue(':limit', max(1,min($limit,100)), PDO::PARAM_INT); $stmt->execute();
    return $stmt->fetchAll();
}
function content_image(PDO $pdo, ?string $filename, string $fallbackAlt): string {
    if (!$filename) return '<div class="community-placeholder" aria-hidden="true"><i class="bi bi-mortarboard"></i></div>';
    $stmt = $pdo->prepare('SELECT alt_text, width, height FROM media_assets WHERE filename = :file');
    $stmt->execute([':file'=>basename($filename)]); $asset = $stmt->fetch();
    $src = BASE_URL . '/uploads/media/' . rawurlencode(basename($filename));
    return '<img src="'.content_escape($src).'" alt="'.content_escape($asset['alt_text'] ?? $fallbackAlt).'" loading="lazy" decoding="async" width="'.(int)($asset['width'] ?? 800).'" height="'.(int)($asset['height'] ?? 600).'">';
}
function validate_content(array $definition, array $input): array {
    $values = [];
    foreach ($definition['fields'] as $key => [$label,$type,$limit]) {
        if (isset($input[$key]) && !is_scalar($input[$key])) throw new InvalidArgumentException("Invalid $label.");
        $value = trim((string)($input[$key] ?? ''));
        if ($type === 'select' && !in_array($value,$limit,true)) throw new InvalidArgumentException("Choose a valid $label.");
        if ($type === 'number' || $type === 'year') {
            if (filter_var($value,FILTER_VALIDATE_INT) === false || (int)$value < ($type === 'year' ? 1950 : 0) || (int)$value > ($type === 'year' ? (int)date('Y')+10 : 100000)) throw new InvalidArgumentException("Enter a valid $label.");
        } elseif ($type !== 'select' && mb_strlen($value) > $limit) throw new InvalidArgumentException("$label is too long.");
        if ($type === 'url') {
            if ($value !== '' && content_url($value) === '') throw new InvalidArgumentException('Use an http or https website URL.');
        } elseif ($value === '') throw new InvalidArgumentException("$label is required.");
        $values[$key] = $value;
    }
    $values['status'] = isset($input['status']) ? 1 : 0;
    return $values;
}
