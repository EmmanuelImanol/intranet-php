// ── Modal open/close ──
const modalOverlay = document.getElementById('modalOverlay');
const btnOpen      = document.getElementById('btnOpenModal');

if (btnOpen) {
  btnOpen.addEventListener('click', () => modalOverlay.classList.add('open'));
}

// Close on overlay click (only when not editing)
modalOverlay.addEventListener('click', (e) => {
  if (e.target === modalOverlay && !window.location.search.includes('edit=')) {
    modalOverlay.classList.remove('open');
  }
});

// ── Auto-hide alerts ──
document.querySelectorAll('.alert').forEach(alert => {
  setTimeout(() => {
    alert.style.transition = 'opacity 0.5s';
    alert.style.opacity = '0';
    setTimeout(() => alert.remove(), 500);
  }, 4000);
});