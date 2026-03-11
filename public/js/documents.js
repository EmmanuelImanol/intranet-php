// ── Upload modal ──
const modalOverlay = document.getElementById('modalOverlay');
const btnOpen      = document.getElementById('btnOpenModal');
const btnClose     = document.getElementById('btnCloseModal');

if (btnOpen)      btnOpen.addEventListener('click', () => modalOverlay.classList.add('open'));
if (btnClose)     btnClose.addEventListener('click', () => modalOverlay.classList.remove('open'));
if (modalOverlay) modalOverlay.addEventListener('click', (e) => {
  if (e.target === modalOverlay) modalOverlay.classList.remove('open');
});

// ── File input display ──
const fileInput = document.getElementById('fileInput');
const fileName  = document.getElementById('fileName');
const fileDrop  = document.getElementById('fileDrop');

if (fileInput) fileInput.addEventListener('change', () => {
  if (fileInput.files.length > 0) {
    fileName.textContent = '✓ ' + fileInput.files[0].name;
  }
});

if (fileDrop) {
  fileDrop.addEventListener('dragover', (e) => {
    e.preventDefault();
    fileDrop.classList.add('dragover');
  });
  fileDrop.addEventListener('dragleave', () => fileDrop.classList.remove('dragover'));
  fileDrop.addEventListener('drop', (e) => {
    e.preventDefault();
    fileDrop.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
      fileInput.files = files;
      fileName.textContent = '✓ ' + files[0].name;
    }
  });
}

// ── Inline document preview ──
const previewPanel = document.getElementById('previewPanel');
const previewTitle = document.getElementById('previewTitle');
const previewBody  = document.getElementById('previewBody');

function openPreview(filePath, docName) {
  if (!previewPanel || !previewTitle || !previewBody) return;
  const ext = filePath.split('.').pop().toLowerCase();
  previewTitle.textContent = docName;
  previewBody.innerHTML = '';

  if (ext === 'pdf') {
    const iframe = document.createElement('iframe');
    iframe.src = filePath;
    previewBody.appendChild(iframe);
  } else if (['jpg', 'jpeg', 'png'].includes(ext)) {
    const img = document.createElement('img');
    img.src = filePath;
    img.alt = docName;
    previewBody.appendChild(img);
  } else if (['mp4', 'webm', 'ogg'].includes(ext)) {
    const video = document.createElement('video');
    video.src = filePath;
    video.controls = true;
    video.autoplay = false;
    video.style.width = '100%';
    video.style.maxHeight = '75vh';
    video.style.display = 'block';
    video.style.background = '#000';
    previewBody.appendChild(video);
  } else {
    previewBody.innerHTML = `
      <div class="preview-placeholder">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
          <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
        </svg>
        <p>Vista previa no disponible para este tipo de archivo</p>
        <span>Descarga el archivo para verlo</span>
      </div>`;
  }

  document.querySelectorAll('.doc-row').forEach(r => r.classList.remove('active'));
  previewPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function openPreviewFromRow(row) {
  if (event.target.closest('.action-btns')) return;
  row.classList.add('active');
  openPreview(row.dataset.file, row.dataset.name);
}

// ── Auto-hide alerts ──
document.querySelectorAll('.alert').forEach(alert => {
  setTimeout(() => {
    alert.style.transition = 'opacity 0.5s';
    alert.style.opacity = '0';
    setTimeout(() => alert.remove(), 500);
  }, 4000);
});

// ── Delete modal ──
const deleteDocOverlay = document.getElementById('deleteDocOverlay');
const deleteDocName    = document.getElementById('deleteDocName');
const deleteDocId      = document.getElementById('deleteDocId');

function openDeleteDocModal(id, name) {
  if (!deleteDocOverlay) return;
  deleteDocName.textContent = name;
  deleteDocId.value = id;
  deleteDocOverlay.classList.add('open');
}

function closeDeleteDocModal() {
  if (deleteDocOverlay) deleteDocOverlay.classList.remove('open');
}

if (deleteDocOverlay) deleteDocOverlay.addEventListener('click', (e) => {
  if (e.target === deleteDocOverlay) closeDeleteDocModal();
});

// ── Auto-open preview from dashboard ──
if (window.__autoView) {
  const { id, filePath, name } = window.__autoView;
  const row = document.querySelector(`.doc-row[data-id="${id}"]`);
  if (row) {
    document.querySelectorAll('.doc-row').forEach(r => r.classList.remove('active'));
    row.classList.add('active');
    setTimeout(() => row.scrollIntoView({ behavior: 'smooth', block: 'center' }), 200);
  }
  openPreview(filePath, name);
}
