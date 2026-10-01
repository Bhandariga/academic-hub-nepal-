/* Local display preference only. No server requests or account mutations. */
(() => {
  const root = document.documentElement;
  let saved;
  try { saved = localStorage.getItem('academicHubTheme'); } catch (_) {}
  root.dataset.theme = saved === 'dark' ? 'dark' : 'light';
  document.addEventListener('DOMContentLoaded', () => {
    const update = () => document.querySelectorAll('[data-theme-toggle]').forEach(button => {
      const dark = root.dataset.theme === 'dark';
      button.setAttribute('aria-pressed', String(dark));
      button.setAttribute('aria-label', `Switch to ${dark ? 'light' : 'dark'} mode`);
      button.innerHTML = `<i class="bi bi-${dark ? 'sun' : 'moon-stars'}" aria-hidden="true"></i>`;
    });
    document.querySelectorAll('[data-theme-toggle]').forEach(button => button.addEventListener('click', () => {
      root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
      try { localStorage.setItem('academicHubTheme', root.dataset.theme); } catch (_) {}
      update();
      let notice = document.getElementById('theme-status');
      if (!notice) { notice = document.createElement('div'); notice.id = 'theme-status'; notice.className = 'theme-toast'; notice.setAttribute('role','status'); document.body.append(notice); }
      notice.textContent = `${root.dataset.theme === 'dark' ? 'Dark' : 'Light'} appearance enabled`;
      notice.hidden = false;
      clearTimeout(notice.hideTimer);
      notice.hideTimer = setTimeout(() => { notice.hidden = true; }, 2200);
    }));
    update();
  });
})();
