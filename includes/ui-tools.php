<div class="ui-tools d-flex align-items-center gap-2 me-2">
 <button type="button" class="btn ui-icon-button" data-theme-toggle aria-label="Switch to dark mode" aria-pressed="false"><i class="bi bi-moon-stars" aria-hidden="true"></i></button>
 <div class="dropdown">
  <button type="button" class="btn ui-icon-button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications"><i class="bi bi-bell" aria-hidden="true"></i></button>
  <div class="dropdown-menu dropdown-menu-end ui-notifications p-3">
   <h2 class="h6 mb-2">Your activity</h2>
   <p class="small text-muted mb-3">Payment status, downloads and service updates are available in your account.</p>
   <a class="dropdown-item rounded-2" href="<?= BASE_URL ?>/dashboard.php#orders-pane"><i class="bi bi-receipt me-2"></i>Orders &amp; receipts</a>
   <a class="dropdown-item rounded-2" href="<?= BASE_URL ?>/dashboard.php#services-pane"><i class="bi bi-headset me-2"></i>Service requests</a>
  </div>
 </div>
</div>
