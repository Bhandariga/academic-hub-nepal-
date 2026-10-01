/**
 * AcademicHub Nepal - Executive Interactive JavaScript Suite
 */
function escapeHubText(value) {
    const span = document.createElement('span');
    span.textContent = String(value);
    return span.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => {
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) {
        const counters = new IntersectionObserver(entries => entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            counters.unobserve(entry.target);
            const target = Number(entry.target.dataset.count);
            const start = performance.now();
            const tick = now => {
                const fraction = Math.min(1, (now - start) / 700);
                entry.target.textContent = Math.round(target * fraction).toLocaleString();
                if (fraction < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        }));
        document.querySelectorAll('[data-count]').forEach(el => counters.observe(el));
    }
    // Derive accessible names from existing labels without changing form field names.
    document.querySelectorAll('input,select,textarea').forEach((field, index) => {
        if (field.type === 'hidden') return;
        const label = field.parentElement.querySelector('label');
        if (label && !label.htmlFor) {
            field.id ||= `hub-field-${index}`;
            label.htmlFor = field.id;
        }
    });
    document.querySelectorAll('input[type="file"]').forEach(field => {
        const info = document.createElement('p');
        info.className = 'hub-upload-info';
        info.setAttribute('aria-live', 'polite');
        field.after(info);
        let preview;
        let url;
        field.addEventListener('change', () => {
            if (url) URL.revokeObjectURL(url);
            preview?.remove();
            const file = field.files[0];
            info.textContent = file ? `${file.name} · ${(file.size / 1048576).toFixed(1)} MB selected` : '';
            if (file && ['image/jpeg','image/png','image/webp'].includes(file.type)) {
                preview = document.createElement('img');
                preview.className = 'hub-upload-preview';
                preview.alt = 'Selected image preview';
                preview.src = url = URL.createObjectURL(file);
                info.after(preview);
            }
        });
    });
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', event => {
            if (event.defaultPrevented || !form.checkValidity()) return;
            form.setAttribute('aria-busy', 'true');
            // Keep submitter names enabled because existing PHP handlers depend on them.
            const button = event.submitter;
            if (button) button.classList.add('hub-busy');
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    // 1. Auto-dismiss alerts after 5 seconds with smooth fade
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // 2. Handle Quick Filter submission on Resources Page
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        const selects = []; // Apply the selected filters together, including on mobile.
        selects.forEach(input => {
            input.addEventListener('change', () => {
                filterForm.submit();
            });
        });
    }

    // 3. Initialize Bootstrap Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 4. Global Keyboard Shortcuts (Ctrl+K or '/' to focus search)
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey && e.key === 'k') || (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA')) {
            const searchInput = document.querySelector('input[name="search"], #tableLiveSearch');
            if (searchInput) {
                e.preventDefault();
                searchInput.focus();
                if (searchInput.select) searchInput.select();
            }
        }
    });

    // 5. Initialize Live Table Search if present
    initTableLiveSearch();
});

/**
 * Instant Client-Side Table Live Search
 * Works on tables with [data-table-search="targetTableId"]
 */
function initTableLiveSearch() {
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(input => {
        const targetId = input.getAttribute('data-table-search');
        const table = document.getElementById(targetId);
        if (!table) return;

        const counter = document.getElementById(targetId + '-count');
        const rows = table.querySelectorAll('tbody tr:not(.no-search)');

        input.addEventListener('input', () => {
            const query = input.value.trim().toLowerCase();
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (counter) {
                counter.textContent = visibleCount;
            }

            // Show empty search state if none found
            let emptyRow = table.querySelector('.empty-search-row');
            if (visibleCount === 0 && rows.length > 0) {
                if (!emptyRow) {
                    const colCount = table.querySelectorAll('thead th').length || 8;
                    emptyRow = document.createElement('tr');
                    emptyRow.className = 'empty-search-row text-center py-4 text-muted';
                    emptyRow.innerHTML = `<td colspan="${colCount}" class="py-5 text-center text-muted">
                        <i class="bi bi-search fs-3 d-block mb-2 opacity-50"></i>
                        No matching records found for "<strong>${escapeHubText(input.value)}</strong>".
                    </td>`;
                    table.querySelector('tbody').appendChild(emptyRow);
                } else {
                    emptyRow.style.display = '';
                }
            } else if (emptyRow) {
                emptyRow.style.display = 'none';
            }
        });
    });
}

/**
 * Toast Notification Utility
 */
function showHubToast(message, icon = 'bi-check-circle-fill', duration = 3500) {
    let container = document.getElementById('hub-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'hub-toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'hub-toast shadow';
    toast.innerHTML = `<i class="bi ${icon} text-info fs-5"></i> <span>${escapeHubText(message)}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

/**
 * Copy text to clipboard with modern toast notification
 */
function copyToClipboard(text, label = 'Copied') {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showHubToast(`${label} to clipboard: ${text}`);
        }).catch(() => {
            fallbackCopy(text, label);
        });
    } else {
        fallbackCopy(text, label);
    }
}

function fallbackCopy(text, label) {
    const input = document.createElement('textarea');
    input.value = text;
    document.body.appendChild(input);
    input.select();
    try {
        document.execCommand('copy');
        showHubToast(`${label} to clipboard!`);
    } catch (err) {
        alert('Copied: ' + text);
    }
    document.body.removeChild(input);
}

/**
 * Toggle Password Visibility (Eye Icon)
 */
function togglePasswordVisibility(inputId, toggleBtn) {
    const field = document.getElementById(inputId);
    if (!field) return;

    const icon = toggleBtn.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    } else {
        field.type = 'password';
        if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
}

/**
 * Export HTML Table Data to CSV
 */
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        if (row.style.display === 'none' || row.classList.contains('empty-search-row')) return;
        let rowData = [];
        const cols = row.querySelectorAll('th, td');
        cols.forEach((col, idx) => {
            // Skip action columns if last column
            if (idx === cols.length - 1 && col.querySelector('button, .btn')) return;
            let data = col.innerText.replace(/"/g, '""').replace(/\n/g, ' ').trim();
            if (/^[=+@\-\t\r]/.test(data)) data = "'" + data;
            rowData.push('"' + data + '"');
        });
        if (rowData.length > 0) csv.push(rowData.join(','));
    });

    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
    showHubToast('Exported table records to CSV successfully!');
}
