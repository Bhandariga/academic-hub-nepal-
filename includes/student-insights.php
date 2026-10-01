<?php
// Uses already-loaded orders and download logs; no new account or payment workflow.
$monthly=[];
foreach($orders as $order) { if($order['payment_status']==='PAID') { $month=date('M Y',strtotime($order['created_at']));$monthly[$month]=($monthly[$month]??0)+(float)$order['amount']; } }
$monthly=array_slice($monthly,0,6,true);$maxMonthly=$monthly?max($monthly):1;
?>
<section class="row g-4 mb-4" aria-label="Your learning activity">
<div class="col-lg-6"><div class="card p-4 h-100"><span class="eyebrow">Your investment in learning</span><h2 class="h5 mt-2 mb-4">Resource spending</h2>
<?php if(!$monthly): ?><p class="text-muted">Your first verified purchase will start your spending overview.</p><?php endif; ?>
<?php foreach($monthly as $month=>$amount): ?><div class="mb-3"><div class="d-flex justify-content-between small mb-1"><span><?= htmlspecialchars($month) ?></span><strong><?= format_npr($amount) ?></strong></div><div class="progress" style="height:8px" aria-hidden="true"><div class="progress-bar" style="background:var(--accent);width:<?= 100*$amount/max(1,$maxMonthly) ?>%"></div></div></div><?php endforeach; ?>
</div></div><div class="col-lg-6"><div class="card p-4 h-100"><span class="eyebrow">Recent milestones</span><h2 class="h5 mt-2 mb-4">Transaction timeline</h2><ol class="transaction-timeline">
<?php foreach(array_slice($orders,0,3) as $order): ?><li><strong class="small"><?= htmlspecialchars($order['resource_title']??$order['title']??'Resource order') ?></strong><div class="small text-muted"><?= htmlspecialchars(date('d M Y',strtotime($order['created_at']))) ?> · <?= htmlspecialchars(str_replace('_',' ',ucfirst(strtolower($order['payment_status'])))) ?> · <?= format_npr($order['amount']) ?></div></li><?php endforeach; ?></ol><?php if(!$orders): ?><p class="text-muted mb-0">Browse the catalog to find your next reference resource.</p><?php endif; ?></div></div>
</section>
