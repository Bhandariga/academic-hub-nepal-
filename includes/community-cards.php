<?php
require_once __DIR__.'/../config/content.php';
function render_community_cards(PDO $pdo,string $module,array $rows,bool $carousel=false): void { ?>
<div class="<?= $carousel?'community-carousel':'row g-4' ?>" <?= $carousel?'tabindex="0" aria-label="Browse community stories"':'' ?>>
<?php foreach($rows as $row): ?><div class="<?= $carousel?'community-slide':'col-md-6 col-xl-4' ?>"><article class="community-card card h-100 overflow-hidden">
<div class="community-photo"><?= content_image($pdo,$row[$module==='gallery'?'image':'photo']??null,$row['name']??$row['college_name']??$row['title']) ?><?php if($module==='students'): ?><span class="photo-badge"><i class="bi bi-award"></i> <?= content_escape($row['achievement']) ?></span><?php endif; ?></div>
<div class="p-4">
<?php if($module==='students'): ?><span class="eyebrow"><?= content_escape($row['program']) ?> · Class of <?= (int)$row['graduation_year'] ?></span><h3 class="h5 mt-2"><?= content_escape($row['name']) ?></h3><p class="text-muted small"><?= content_escape($row['campus']) ?></p><blockquote class="mb-0 community-story">“<?= nl2br(content_escape($row['story'])) ?>”</blockquote>
<?php elseif($module==='colleges'): ?><span class="eyebrow"><?= content_escape($row['university']) ?></span><h3 class="h5 mt-2"><?= content_escape($row['college_name']) ?></h3><p class="small text-muted"><i class="bi bi-geo-alt"></i> <?= content_escape($row['location']) ?></p><p><?= nl2br(content_escape($row['description'])) ?></p><p class="small fw-semibold"><?= content_escape($row['programs']) ?></p><?php if(content_url($row['website'])): ?><a class="btn btn-outline-primary" href="<?= content_escape(content_url($row['website'])) ?>" target="_blank" rel="noopener noreferrer">Visit college <i class="bi bi-arrow-up-right"></i></a><?php endif; ?>
<?php else: ?><span class="eyebrow"><?= content_escape($row['category']) ?></span><h3 class="h5 mt-2"><?= content_escape($row['title']) ?></h3><p class="mb-0"><?= nl2br(content_escape($row['description'])) ?></p><?php endif; ?>
</div></article></div><?php endforeach; ?>
</div>
<?php if(!$rows): ?><div class="empty-state card"><i class="bi <?= $module==='students'?'bi-mortarboard':($module==='colleges'?'bi-buildings':'bi-camera') ?>"></i><h3 class="h5"><?= $module==='students'?'Every achievement has a story':($module==='colleges'?'Growing our academic network':'A space for shared learning') ?></h3><p><?= $module==='students'?'Approved student stories will appear here.':($module==='colleges'?'College profiles will appear here as they are published.':'Campus activities, workshops and research events will appear here.') ?></p><a href="<?= BASE_URL ?>/services.php" class="btn btn-outline-primary">Connect with AcademicHub</a></div><?php endif; ?>
<?php }
