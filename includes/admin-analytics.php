<?php
/** Compact, accessible charts derived only from paid orders. */
if (admin_has_permission('view_reports')):
$charts = [
    'Monthly revenue' => "SELECT DATE_FORMAT(created_at, '%Y-%m') AS label, SUM(amount) AS value FROM orders WHERE payment_status = 'PAID' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY label ORDER BY label",
    'Campus sales' => "SELECT r.campus AS label, SUM(o.amount) AS value FROM orders o JOIN resources r ON r.id = o.resource_id WHERE o.payment_status = 'PAID' GROUP BY r.campus ORDER BY value DESC LIMIT 5",
    'Program sales' => "SELECT r.program AS label, SUM(o.amount) AS value FROM orders o JOIN resources r ON r.id = o.resource_id WHERE o.payment_status = 'PAID' GROUP BY r.program ORDER BY value DESC LIMIT 5",
    'Popular resources' => "SELECT r.title AS label, COUNT(*) AS value FROM orders o JOIN resources r ON r.id = o.resource_id WHERE o.payment_status = 'PAID' GROUP BY r.id, r.title ORDER BY value DESC LIMIT 5"
];
?>
<section class="row g-3 mb-4" aria-label="Sales analytics">
<?php foreach ($charts as $title => $query):
    $statement = $pdo->prepare($query); $statement->execute(); $series = $statement->fetchAll();
    $maximum = $series ? max(array_column($series, 'value')) : 1;
?>
<div class="col-xl-6"><div class="card h-100 p-4">
    <h3 class="h6 mb-3"><?= htmlspecialchars($title) ?></h3>
    <?php if (!$series): ?><p class="text-muted small mb-0">Your first verified sale will appear here.</p><?php endif; ?>
    <?php foreach ($series as $point): ?>
    <div class="mb-3">
        <div class="d-flex justify-content-between gap-3 small mb-1"><span class="text-truncate" title="<?= htmlspecialchars($point['label']) ?>"><?= htmlspecialchars($point['label']) ?></span><strong class="text-nowrap"><?= $title === 'Popular resources' ? (int)$point['value'] . ' orders' : format_npr($point['value']) ?></strong></div>
        <div class="progress" style="height:8px" aria-hidden="true"><div class="progress-bar" style="background:var(--accent);width:<?= max(1, 100 * $point['value'] / max(1, $maximum)) ?>%"></div></div>
    </div>
    <?php endforeach; ?>
</div></div>
<?php endforeach; ?>
</section>
<?php endif; ?>
