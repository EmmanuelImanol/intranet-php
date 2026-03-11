<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];
$user_area = $_SESSION['user_area'] ?? '';
$db        = connectDB();

$area_labels = [
    'administracion_finanzas' => 'Administración y Finanzas',
    'comercial'               => 'Comercial',
    'controller'              => 'Controller',
    'culinary_innovacion'     => 'Culinary & Innovación',
    'direccion_general'       => 'Dirección General',
    'finanzas'                => 'Finanzas',
    'fiscal'                  => 'Fiscal',
    'mantenimiento'           => 'Mantenimiento',
    'recursos_humanos'        => 'Recursos Humanos',
    'restaurantes'            => 'Restaurantes',
    'operaciones'             => 'Operaciones',
];

$role_labels = [
    'admin'      => 'Administrador',
    'supervisor' => 'Supervisor',
    'empleado'   => 'Empleado',
];

// Fetch fresh user data from DB
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ../../login.php');
    exit;
}

$error   = '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

// ── Update name ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_name') {
    $new_name = trim($_POST['name'] ?? '');

    if (empty($new_name)) {
        $error = 'El nombre no puede estar vacío.';
    } elseif (strlen($new_name) < 2) {
        $error = 'El nombre debe tener al menos 2 caracteres.';
    } else {
        $stmt = $db->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->execute([$new_name, $user_id]);
        $_SESSION['user_name'] = $new_name;
        $_SESSION['flash_success'] = 'Nombre actualizado correctamente.';
        header('Location: profile.php');
        exit;
    }
}

// ── Update password ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_password') {
    $current  = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password']     ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new_pass) || empty($confirm)) {
        $error = 'Todos los campos de contraseña son obligatorios.';
    } elseif (!password_verify($current, $user['password'])) {
        $error = 'La contraseña actual es incorrecta.';
    } elseif (strlen($new_pass) < 6) {
        $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } elseif ($new_pass !== $confirm) {
        $error = 'Las contraseñas nuevas no coinciden.';
    } else {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $user_id]);
        $_SESSION['flash_success'] = 'Contraseña actualizada correctamente.';
        header('Location: profile.php');
        exit;
    }
}

// Avatar color (same logic as users.php)
$avatar_colors = ['#6366f1','#ec4899','#14b8a6','#f59e0b','#3b82f6','#8b5cf6','#ef4444','#10b981'];
$avatar_color  = $avatar_colors[ord(strtoupper($user['name'][0])) % count($avatar_colors)];

$area_display = $area_labels[$user_area] ?? ($user_area ? ucfirst($user_area) : '—');
$role_display = $role_labels[$user_role] ?? ucfirst($user_role);
$member_since = date('d \d\e F \d\e Y', strtotime($user['created_at']));
?>
<?php $page_title = 'Mi perfil'; $extra_css = ['../../public/css/profile.css']; include __DIR__ . '/../components/head.php'; ?>
<body>

  <?php $active_page = 'profile'; include __DIR__ . '/../components/sidebar.php'; ?>

  <main class="main-content">

    <?php
      $topbar_title    = 'Mi <span>Perfil</span>';
      $topbar_subtitle = 'Gestiona tu información personal';
      ob_start(); ?>
      <div class="topbar-badge"><?= $role_display ?></div>
    <?php $topbar_action = ob_get_clean();
    include __DIR__ . '/../components/topbar.php'; ?>

    <div class="profile-layout">

      <!-- ── Columna izquierda: tarjeta de identidad ── -->
      <aside class="profile-card">
        <div class="avatar-ring" style="--avatar-color: <?= $avatar_color ?>">
          <div class="avatar-circle"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
        </div>

        <h2 class="profile-name"><?= htmlspecialchars($user['name']) ?></h2>
        <p class="profile-email"><?= htmlspecialchars($user['email']) ?></p>

        <div class="profile-badges">
          <span class="badge badge-role"><?= $role_display ?></span>
          <?php if ($area_display !== '—'): ?>
          <span class="badge badge-area"><?= $area_display ?></span>
          <?php endif; ?>
        </div>

        <div class="profile-meta">
          <div class="meta-item">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <span>Miembro desde <?= $member_since ?></span>
          </div>
          <div class="meta-item">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span><?= $area_display ?></span>
          </div>
        </div>
      </aside>

      <!-- ── Columna derecha: formularios ── -->
      <div class="profile-forms">

        <?php if ($success): ?>
        <div class="alert alert-success">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Información general -->
        <section class="form-section">
          <div class="section-header">
            <div class="section-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
            </div>
            <div>
              <h3>Información general</h3>
              <p>Actualiza tu nombre visible en el sistema</p>
            </div>
          </div>

          <form method="POST" class="profile-form">
            <input type="hidden" name="action" value="update_name" />

            <div class="field-row">
              <div class="field-group">
                <label>Nombre completo</label>
                <input type="text" name="name"
                  value="<?= htmlspecialchars($user['name']) ?>"
                  placeholder="Tu nombre" required />
              </div>
              <div class="field-group">
                <label>Correo electrónico</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled />
                <span class="field-hint">El email no se puede cambiar</span>
              </div>
            </div>

            <div class="field-row">
              <div class="field-group">
                <label>Rol</label>
                <input type="text" value="<?= $role_display ?>" disabled />
              </div>
              <div class="field-group">
                <label>Área</label>
                <input type="text" value="<?= $area_display ?>" disabled />
              </div>
            </div>

            <div class="form-footer">
              <button type="submit" class="btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                  <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                </svg>
                Guardar cambios
              </button>
            </div>
          </form>
        </section>

        <!-- Cambiar contraseña -->
        <section class="form-section">
          <div class="section-header">
            <div class="section-icon section-icon--pink">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
              </svg>
            </div>
            <div>
              <h3>Cambiar contraseña</h3>
              <p>Usa una contraseña segura de al menos 6 caracteres</p>
            </div>
          </div>

          <form method="POST" class="profile-form" id="passForm">
            <input type="hidden" name="action" value="update_password" />

            <div class="field-group">
              <label>Contraseña actual</label>
              <div class="pass-wrapper">
                <input type="password" name="current_password" id="currentPass" placeholder="••••••••" required />
                <button type="button" class="toggle-pass" data-target="currentPass">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>
              </div>
            </div>

            <div class="field-row">
              <div class="field-group">
                <label>Nueva contraseña</label>
                <div class="pass-wrapper">
                  <input type="password" name="new_password" id="newPass" placeholder="Mínimo 6 caracteres" required />
                  <button type="button" class="toggle-pass" data-target="newPass">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                  </button>
                </div>
              </div>
              <div class="field-group">
                <label>Confirmar contraseña</label>
                <div class="pass-wrapper">
                  <input type="password" name="confirm_password" id="confirmPass" placeholder="Repite la contraseña" required />
                  <button type="button" class="toggle-pass" data-target="confirmPass">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Strength bar -->
            <div class="strength-wrap" id="strengthWrap" style="display:none">
              <div class="strength-bar">
                <div class="strength-fill" id="strengthFill"></div>
              </div>
              <span class="strength-label" id="strengthLabel"></span>
            </div>

            <div class="form-footer">
              <button type="submit" class="btn-primary btn-pink">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
                </svg>
                Actualizar contraseña
              </button>
            </div>
          </form>
        </section>

      </div>
    </div>
  </main>

<?php $extra_js = ['../../public/js/profile.js'];
  include __DIR__ . '/../components/footer.php'; ?>
