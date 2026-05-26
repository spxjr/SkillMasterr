// Texas Skill Masters — Sales Rep Portal JS

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
}

document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  const tb = document.getElementById('sidebarToggle');
  if (window.innerWidth <= 900 && sb && sb.classList.contains('open')) {
    if (!sb.contains(e.target) && tb && !tb.contains(e.target)) sb.classList.remove('open');
  }
});

function openModal(id)  { const m = document.getElementById(id); if(m){m.classList.add('open'); document.body.style.overflow='hidden';} }
function closeModal(id) { const m = document.getElementById(id); if(m){m.classList.remove('open'); document.body.style.overflow='';} }

document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) { e.target.classList.remove('open'); document.body.style.overflow=''; }
});
document.addEventListener('keydown', e => {
  if (e.key==='Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => { m.classList.remove('open'); document.body.style.overflow=''; });
});

function initTabs() {
  document.querySelectorAll('.tab-nav').forEach(nav => {
    nav.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const target = btn.dataset.tab;
        const cont   = btn.closest('.tab-container') || document;
        cont.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        cont.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        const panel = cont.querySelector(`[data-panel="${target}"]`);
        if (panel) panel.classList.add('active');
      });
    });
  });
}

function animateBars() {
  setTimeout(() => {
    document.querySelectorAll('[data-progress]').forEach(el => {
      el.style.width = Math.min(100, el.dataset.progress) + '%';
    });
  }, 200);
}

setTimeout(() => {
  const f = document.getElementById('flashAlert');
  if (f) { f.style.transition='opacity .5s'; f.style.opacity='0'; setTimeout(()=>f.remove(),500); }
}, 4500);

function togglePw(fieldId, iconId) {
  const f = document.getElementById(fieldId);
  const i = document.getElementById(iconId);
  if (!f) return;
  if (f.type==='password') { f.type='text'; if(i) i.className='fa-solid fa-eye-slash'; }
  else { f.type='password'; if(i) i.className='fa-solid fa-eye'; }
}

document.addEventListener('DOMContentLoaded', () => { initTabs(); animateBars(); });
