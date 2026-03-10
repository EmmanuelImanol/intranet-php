// ── Toggle switches ──
const unsavedHint = document.getElementById('unsavedHint');
let isDirty = false;

document.querySelectorAll('.toggle:not(.toggle--disabled)').forEach(label => {
  const input = label.querySelector('.toggle-input');
  if (!input) return;

  label.addEventListener('click', () => {
    input.checked = !input.checked;
    label.classList.toggle('toggle--on', input.checked);
    markDirty();

    // Rule: if can_view is unchecked, uncheck all others for that module+role
    if (input.dataset.action === 'can_view' && !input.checked) {
      const module = input.dataset.module;
      const role   = input.dataset.role;
      document.querySelectorAll(`.toggle-input[data-module="${module}"][data-role="${role}"]`).forEach(other => {
        if (other !== input) {
          other.checked = false;
          other.closest('.toggle')?.classList.remove('toggle--on');
        }
      });
    }

    // Rule: if any action other than can_view is checked, auto-check can_view
    if (input.dataset.action !== 'can_view' && input.checked) {
      const module   = input.dataset.module;
      const role     = input.dataset.role;
      const viewInput = document.querySelector(`.toggle-input[data-module="${module}"][data-role="${role}"][data-action="can_view"]`);
      if (viewInput && !viewInput.checked) {
        viewInput.checked = true;
        viewInput.closest('.toggle')?.classList.add('toggle--on');
      }
    }
  });
});

function markDirty() {
  if (!isDirty) {
    isDirty = true;
    if (unsavedHint) unsavedHint.style.display = 'flex';
  }
}

// Warn on leaving with unsaved changes
window.addEventListener('beforeunload', e => {
  if (isDirty) {
    e.preventDefault();
    e.returnValue = '';
  }
});

// Clear dirty on submit
document.getElementById('permissionsForm')?.addEventListener('submit', () => {
  isDirty = false;
});