// ── Modal open/close ──
const modalOverlay = document.getElementById('modalOverlay');
const btnOpen      = document.getElementById('btnOpenModal');
const btnClose     = document.getElementById('btnCloseModal');

if (btnOpen) {
  btnOpen.addEventListener('click', () => modalOverlay.classList.add('open'));
}

// Close modal on overlay click (only for create, not edit)
modalOverlay.addEventListener('click', (e) => {
  if (e.target === modalOverlay && !window.location.search.includes('edit=')) {
    modalOverlay.classList.remove('open');
  }
});

// ── Password toggle ──
const togglePass = document.getElementById('togglePass');
const passInput  = document.getElementById('modalPassword');
const eyeIcon    = document.getElementById('eyeIcon');

const eyeOpen   = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
const eyeClosed = `<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>`;

if (togglePass) {
  togglePass.addEventListener('click', () => {
    const isPass = passInput.type === 'password';
    passInput.type = isPass ? 'text' : 'password';
    eyeIcon.innerHTML = isPass ? eyeClosed : eyeOpen;
  });
}

// ── Auto-hide alerts ──
document.querySelectorAll('.alert').forEach(alert => {
  setTimeout(() => {
    alert.style.transition = 'opacity 0.5s';
    alert.style.opacity = '0';
    setTimeout(() => alert.remove(), 500);
  }, 4000);
});

// ── Delete confirmation modal ──
const deleteOverlay  = document.getElementById('deleteOverlay');
const deleteUserName = document.getElementById('deleteUserName');
const deleteUserId   = document.getElementById('deleteUserId');

function openDeleteModal(id, name) {
  deleteUserName.textContent = name;
  deleteUserId.value         = id;
  deleteOverlay.classList.add('open');
}

function closeDeleteModal() {
  deleteOverlay.classList.remove('open');
}

deleteOverlay.addEventListener('click', (e) => {
  if (e.target === deleteOverlay) closeDeleteModal();
});