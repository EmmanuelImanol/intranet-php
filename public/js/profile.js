// ── Toggle password visibility ──
document.querySelectorAll('.toggle-pass').forEach(btn => {
  btn.addEventListener('click', () => {
    const targetId = btn.dataset.target;
    const input    = document.getElementById(targetId);
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.style.opacity = input.type === 'text' ? '1' : '0.5';
  });
});

// ── Password strength indicator ──
const newPassInput  = document.getElementById('newPass');
const strengthWrap  = document.getElementById('strengthWrap');
const strengthFill  = document.getElementById('strengthFill');
const strengthLabel = document.getElementById('strengthLabel');

function getStrength(pwd) {
  let score = 0;
  if (pwd.length >= 6)  score++;
  if (pwd.length >= 10) score++;
  if (/[A-Z]/.test(pwd)) score++;
  if (/[0-9]/.test(pwd)) score++;
  if (/[^A-Za-z0-9]/.test(pwd)) score++;
  return score;
}

const levels = [
  { label: 'Muy débil',  color: '#ef4444', width: '15%' },
  { label: 'Débil',      color: '#f97316', width: '30%' },
  { label: 'Regular',    color: '#f59e0b', width: '55%' },
  { label: 'Buena',      color: '#22c55e', width: '78%' },
  { label: 'Fuerte',     color: '#10b981', width: '100%' },
];

if (newPassInput) {
  newPassInput.addEventListener('input', () => {
    const val = newPassInput.value;
    if (!val) {
      strengthWrap.style.display = 'none';
      return;
    }
    strengthWrap.style.display = 'flex';
    const score = Math.min(getStrength(val), 4);
    const lvl   = levels[score];
    strengthFill.style.width      = lvl.width;
    strengthFill.style.background = lvl.color;
    strengthLabel.textContent     = lvl.label;
    strengthLabel.style.color     = lvl.color;
  });
}

// ── Client-side password match check ──
const passForm    = document.getElementById('passForm');
const confirmPass = document.getElementById('confirmPass');

if (passForm && confirmPass) {
  passForm.addEventListener('submit', e => {
    const newVal     = document.getElementById('newPass').value;
    const confirmVal = confirmPass.value;
    if (newVal !== confirmVal) {
      e.preventDefault();
      confirmPass.style.borderColor = '#ef4444';
      confirmPass.style.boxShadow   = '0 0 0 3px rgba(239,68,68,0.15)';
      confirmPass.focus();
    }
  });
  confirmPass.addEventListener('input', () => {
    confirmPass.style.borderColor = '';
    confirmPass.style.boxShadow   = '';
  });
}
