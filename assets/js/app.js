// Texas Skill Masters CRM — App JS

// Sidebar toggle
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
}

// Close sidebar on overlay click (mobile)
document.addEventListener('click', function(e) {
  const sidebar = document.getElementById('sidebar');
  const toggle  = document.getElementById('sidebarToggle');
  if (window.innerWidth <= 900 && sidebar.classList.contains('open')) {
    if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  }
});

// Modal helpers
function openModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
}

function closeModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
}

// Close modal on overlay click
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
    document.body.style.overflow = '';
  }
});

// ESC key closes modals
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => {
      m.classList.remove('open');
      document.body.style.overflow = '';
    });
  }
});

// Tab navigation
function initTabs() {
  document.querySelectorAll('.tab-nav').forEach(nav => {
    nav.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const target = btn.dataset.tab;
        const container = btn.closest('.tab-container') || document;
        container.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        container.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        const panel = container.querySelector(`[data-panel="${target}"]`);
        if (panel) panel.classList.add('active');
      });
    });
  });
}

// Auto-hide flash alerts
setTimeout(() => {
  const flash = document.getElementById('flashAlert');
  if (flash) { flash.style.opacity = '0'; flash.style.transition = 'opacity .5s'; setTimeout(() => flash.remove(), 500); }
}, 4500);

// Live table search
function liveSearch(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table) return;
  input.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// Confirm delete
function confirmDelete(msg) {
  return confirm(msg || 'Are you sure you want to delete this record? This cannot be undone.');
}

// Format currency input
function formatCurrencyInput(el) {
  el.addEventListener('blur', () => {
    const val = parseFloat(el.value);
    if (!isNaN(val)) el.value = val.toFixed(2);
  });
}

// Animate stat cards on load
function animateStats() {
  document.querySelectorAll('.stat-value[data-val]').forEach(el => {
    const target = parseFloat(el.dataset.val.replace(/[^0-9.]/g, ''));
    const prefix = el.dataset.val.startsWith('$') ? '$' : '';
    const suffix = el.dataset.val.endsWith('%') ? '%' : '';
    let current = 0;
    const step = target / 40;
    const timer = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = prefix + (Number.isInteger(target) ? Math.round(current) : current.toFixed(2)) + suffix;
      if (current >= target) clearInterval(timer);
    }, 25);
  });
}

// Animate bar chart fills
function animateBars() {
  document.querySelectorAll('.bar-fill[data-w]').forEach(el => {
    setTimeout(() => { el.style.width = el.dataset.w + '%'; }, 100);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initTabs();
  animateStats();
  animateBars();
  // Currency inputs
  document.querySelectorAll('input[data-currency]').forEach(formatCurrencyInput);
});
