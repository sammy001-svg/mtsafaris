/* =============================================================
   MT SAFARIS — Admin JavaScript
   ============================================================= */

(function () {
  'use strict';

  // ---- Sidebar toggle (mobile) ----
  const toggle  = document.querySelector('.sidebar-toggle');
  const sidebar = document.querySelector('.admin-sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', e => {
      if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  // ---- Notification dropdown ----
  const notifBtn = document.getElementById('notifBtn');
  const notifDrop = document.getElementById('notifDropdown');
  if (notifBtn && notifDrop) {
    notifBtn.addEventListener('click', e => {
      e.stopPropagation();
      notifDrop.classList.toggle('open');
    });
    document.addEventListener('click', () => notifDrop.classList.remove('open'));
  }

  // ---- Modal helpers ----
  window.openModal = function (id) {
    const m = document.getElementById(id);
    if (m) m.classList.add('open');
    document.body.style.overflow = 'hidden';
  };
  window.closeModal = function (id) {
    const m = document.getElementById(id);
    if (m) m.classList.remove('open');
    document.body.style.overflow = '';
  };
  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      btn.closest('.modal-backdrop')?.classList.remove('open');
      document.body.style.overflow = '';
    });
  });
  document.querySelectorAll('.modal-backdrop').forEach(bd => {
    bd.addEventListener('click', e => {
      if (e.target === bd) { bd.classList.remove('open'); document.body.style.overflow = ''; }
    });
  });

  // ---- Delete confirm ----
  document.querySelectorAll('[data-confirm-delete]').forEach(btn => {
    btn.addEventListener('click', e => {
      if (!confirm(btn.dataset.confirmDelete || 'Are you sure you want to delete this item? This cannot be undone.')) {
        e.preventDefault();
      }
    });
  });

  // ---- Toggle switch (form) ----
  document.querySelectorAll('.toggle input').forEach(input => {
    input.addEventListener('change', () => {
      const hiddenName = input.dataset.target;
      if (hiddenName) {
        const hidden = document.querySelector(`input[name="${hiddenName}"]`);
        if (hidden) hidden.value = input.checked ? '1' : '0';
      }
    });
  });

  // ---- Upload drag & drop ----
  document.querySelectorAll('.upload-area').forEach(area => {
    const input = area.querySelector('input[type="file"]');
    const preview = area.closest('.upload-wrap')?.querySelector('.upload-preview');

    ['dragenter','dragover'].forEach(e => area.addEventListener(e, ev => {
      ev.preventDefault(); area.classList.add('drag-over');
    }));
    ['dragleave','drop'].forEach(e => area.addEventListener(e, ev => {
      ev.preventDefault(); area.classList.remove('drag-over');
    }));
    area.addEventListener('drop', ev => {
      if (input) { input.files = ev.dataTransfer.files; previewFiles(input, preview); }
    });
    area.addEventListener('click', () => input?.click());
    input?.addEventListener('change', () => previewFiles(input, preview));
  });

  function previewFiles(input, preview) {
    if (!preview) return;
    preview.innerHTML = '';
    Array.from(input.files).forEach(file => {
      if (!file.type.startsWith('image/')) return;
      const reader = new FileReader();
      reader.onload = e => {
        const item = document.createElement('div');
        item.className = 'preview-item';
        item.innerHTML = `<img src="${e.target.result}" alt="">
          <button type="button" class="remove"><i class="fas fa-times"></i></button>`;
        item.querySelector('.remove').addEventListener('click', () => item.remove());
        preview.appendChild(item);
      };
      reader.readAsDataURL(file);
    });
  }

  // ---- Inline slug generation ----
  const titleInput = document.getElementById('package_title');
  const slugInput  = document.getElementById('package_slug');
  if (titleInput && slugInput && !slugInput.value) {
    titleInput.addEventListener('input', () => {
      slugInput.value = titleInput.value
        .toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_]+/g, '-')
        .replace(/-+/g, '-');
    });
  }

  // ---- Rich text editor (minimal contenteditable) ----
  document.querySelectorAll('[data-editor]').forEach(container => {
    const toolbar = container.querySelector('.editor-toolbar');
    const content = container.querySelector('.editor-content');
    const hidden  = container.querySelector('input[type="hidden"], textarea[data-output]');

    if (!toolbar || !content) return;

    toolbar.querySelectorAll('button[data-cmd]').forEach(btn => {
      btn.addEventListener('mousedown', e => {
        e.preventDefault();
        const cmd   = btn.dataset.cmd;
        const value = btn.dataset.value || null;
        document.execCommand(cmd, false, value);
        content.focus();
        syncEditor();
      });
    });

    content.addEventListener('input', syncEditor);
    function syncEditor() {
      if (hidden) hidden.value = content.innerHTML;
    }
    if (hidden && hidden.value) content.innerHTML = hidden.value;
  });

  // ---- Data table search ----
  const tableSearch = document.getElementById('tableSearch');
  if (tableSearch) {
    tableSearch.addEventListener('input', () => {
      const q = tableSearch.value.toLowerCase();
      document.querySelectorAll('.admin-table tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  // ---- Bulk select ----
  const selectAll = document.getElementById('selectAll');
  if (selectAll) {
    selectAll.addEventListener('change', () => {
      document.querySelectorAll('.row-select').forEach(cb => cb.checked = selectAll.checked);
      updateBulkBar();
    });
    document.querySelectorAll('.row-select').forEach(cb => {
      cb.addEventListener('change', updateBulkBar);
    });
  }

  const bulkBar = document.getElementById('bulkBar');
  function updateBulkBar() {
    const count = document.querySelectorAll('.row-select:checked').length;
    if (bulkBar) {
      bulkBar.style.display = count > 0 ? 'flex' : 'none';
      const countEl = bulkBar.querySelector('.bulk-count');
      if (countEl) countEl.textContent = count + ' selected';
    }
  }

  // ---- Chart init (Chart.js, if loaded) ----
  function initCharts() {
    if (typeof Chart === 'undefined') return;

    // Revenue chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
      const months = revenueCtx.dataset.labels ? JSON.parse(revenueCtx.dataset.labels) : [];
      const data   = revenueCtx.dataset.values ? JSON.parse(revenueCtx.dataset.values) : [];
      new Chart(revenueCtx, {
        type: 'line',
        data: {
          labels: months,
          datasets: [{
            label: 'Revenue (USD)',
            data,
            borderColor: '#0C2614',
            backgroundColor: 'rgba(12,38,20,.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#0C2614',
            pointRadius: 4,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: {
              grid: { color: '#E2E8F0' },
              ticks: { callback: v => '$' + v.toLocaleString() },
            },
            x: { grid: { display: false } },
          },
        },
      });
    }

    // Bookings chart (doughnut)
    const bookingsCtx = document.getElementById('bookingsChart');
    if (bookingsCtx) {
      const labels = bookingsCtx.dataset.labels ? JSON.parse(bookingsCtx.dataset.labels) : [];
      const data   = bookingsCtx.dataset.values ? JSON.parse(bookingsCtx.dataset.values) : [];
      new Chart(bookingsCtx, {
        type: 'doughnut',
        data: {
          labels,
          datasets: [{
            data,
            backgroundColor: ['#0C2614','#F6A229','#3BAFDA','#38A169','#E53E3E'],
            borderWidth: 2,
            borderColor: '#fff',
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom', labels: { padding: 16, font: { size: 11 } } },
          },
          cutout: '68%',
        },
      });
    }

    // Package performance (bar)
    const packagesCtx = document.getElementById('packagesChart');
    if (packagesCtx) {
      const labels = packagesCtx.dataset.labels ? JSON.parse(packagesCtx.dataset.labels) : [];
      const data   = packagesCtx.dataset.values ? JSON.parse(packagesCtx.dataset.values) : [];
      new Chart(packagesCtx, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Bookings',
            data,
            backgroundColor: '#F6A229',
            borderRadius: 6,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { grid: { color: '#E2E8F0' }, beginAtZero: true },
            x: { grid: { display: false } },
          },
        },
      });
    }
  }
  initCharts();

  // ---- Toast ----
  window.adminToast = function (msg, type = 'info') {
    const colors = { success: '#38A169', error: '#E53E3E', warning: '#D69E2E', info: '#3182CE' };
    let container = document.querySelector('.admin-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'admin-toast-container';
      container.style.cssText = 'position:fixed;top:80px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
      document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.style.cssText = `background:#fff;border-left:3px solid ${colors[type]};
      padding:12px 16px;border-radius:6px;box-shadow:0 4px 16px rgba(0,0,0,.1);
      font-size:.82rem;display:flex;gap:8px;align-items:center;min-width:240px;`;
    toast.innerHTML = `<span style="color:${colors[type]};font-weight:700">${type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'}</span>
      <span>${msg}</span>`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .3s'; setTimeout(() => toast.remove(), 300); }, 3500);
  };

})();
