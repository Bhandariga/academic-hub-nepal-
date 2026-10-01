<?php
require_once __DIR__.'/../config/content.php';
function render_team_cards(array $members,bool $compact=false): void { ?>
<div class="row g-4">
<?php foreach($members as $member): $name=content_escape($member['name']);$category=$member['category']??'Academic Advisors';$photo=$member['photo']??'';$id=(int)$member['id']; ?>
<div class="col-md-6 col-lg-4" data-team-category="<?= content_escape($category) ?>"><article class="card community-card h-100 overflow-hidden"><div class="team-card-top">
<?php if($photo && is_file(__DIR__.'/../uploads/team/'.basename($photo))): ?><img class="team-avatar" src="<?= BASE_URL ?>/uploads/team/<?= rawurlencode(basename($photo)) ?>" alt="<?= $name ?>" loading="lazy" width="112" height="112">
<?php else: ?><div class="team-avatar team-initial" aria-hidden="true"><?= content_escape(mb_substr($member['name'],0,1)) ?></div><?php endif; ?>
<span class="eyebrow mt-3"><?= content_escape($category) ?></span><h3 class="h5 mt-2 mb-1"><?= $name ?></h3><p class="text-primary small mb-0"><?= content_escape($member['role_title']) ?></p></div>
<div class="p-4 d-flex flex-column flex-grow-1"><p class="text-muted small"><?= content_escape($member['department']??'') ?></p><p class="text-muted"><?= content_escape(mb_strimwidth($member['bio']??'',0,180,'…')) ?></p><div class="d-flex gap-2 align-items-center mt-auto"><button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#team-profile-<?= $id ?>">View profile</button><?php if(content_url($member['linkedin_url']??'')): ?><a class="btn btn-light" href="<?= content_escape(content_url($member['linkedin_url'])) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= $name ?> on LinkedIn"><i class="bi bi-linkedin"></i></a><?php endif; ?></div></div>
</article></div>
<?php endforeach; ?></div>
<?php foreach($members as $member):$id=(int)$member['id']; ?>
<div class="modal fade" id="team-profile-<?= $id ?>" tabindex="-1" aria-labelledby="team-name-<?= $id ?>" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4"><div class="modal-header"><h2 class="modal-title h5" id="team-name-<?= $id ?>"><?= content_escape($member['name']) ?></h2><button class="btn-close" data-bs-dismiss="modal" aria-label="Close profile"></button></div><div class="modal-body p-4"><span class="eyebrow"><?= content_escape($member['category']??'Academic Advisors') ?></span><h3 class="h5 mt-2"><?= content_escape($member['role_title']) ?></h3><p class="text-muted"><?= content_escape($member['department']??'') ?></p><p><?= nl2br(content_escape($member['bio']??'')) ?></p><div class="d-flex flex-wrap gap-2">
<?php if(filter_var($member['email']??'',FILTER_VALIDATE_EMAIL)): ?><a class="btn btn-outline-primary" href="mailto:<?= content_escape($member['email']) ?>"><i class="bi bi-envelope"></i> Email</a><?php endif; ?>
<?php foreach(['linkedin_url'=>'LinkedIn','facebook_url'=>'Facebook','twitter_url'=>'X / Twitter'] as $field=>$label):if(content_url($member[$field]??'')): ?><a class="btn btn-light" href="<?= content_escape(content_url($member[$field])) ?>" target="_blank" rel="noopener noreferrer"><?= $label ?> <i class="bi bi-arrow-up-right"></i></a><?php endif;endforeach; ?></div></div></div></div></div>
<?php endforeach; ?>
<?php if(!$members): ?><div class="empty-state card"><h3 class="h5">Meet our team soon</h3><p>Published team profiles will appear here.</p></div><?php endif; ?>
<?php }
