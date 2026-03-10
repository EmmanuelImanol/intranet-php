<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Only admins can access settings
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];
$user_area = $_SESSION['user_area'] ?? '';
$db        = connectDB();

$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);
$error = '';

$modules = ['noticias', 'documentos', 'usuarios'];
$roles   = ['supervisor', 'empleado'];
$actions = ['can_view', 'can_create', 'can_edit', 'can_delete'];
$action_labels = [
    'can_view'   => 'Ver',
    'can_create' => 'Crear',
    'can_edit'   => 'Editar',
    'can_delete' => 'Eliminar',
];
$module_labels = [
    'noticias'   => 'Noticias',
    'documentos' => 'Documentos',
    'usuarios'   => 'Usuarios',
];
$module_icons = [
    'noticias'   => '<path d="M4 22h16a2 2 0 002-2V4a2 2 0 00-2-2H8a2 2 0 00-2 2v16a2 2 0 002 2z"/><path d="M8 6h8M8 10h8M8 14h4"/>',
    'documentos' => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>',
    'usuarios'   => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>',
];
$role_labels = [
    'supervisor' => 'Supervisor',
    'empleado'   => 'Empleado',
];

// ── Save permissions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_permissions') {
    try {
        $stmt = $db->prepare("
            INSERT INTO permissions (module, role, can_view, can_create, can_edit, can_delete)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              can_view   = VALUES(can_view),
              can_create = VALUES(can_create),
              can_edit   = VALUES(can_edit),
              can_delete = VALUES(can_delete)
        ");

        foreach ($modules as $module) {
            foreach ($roles as $role) {
                $key = "{$module}_{$role}";
                $stmt->execute([
                    $module,
                    $role,
                    isset($_POST["{$key}_can_view"])   ? 1 : 0,
                    isset($_POST["{$key}_can_create"]) ? 1 : 0,
                    isset($_POST["{$key}_can_edit"])   ? 1 : 0,
                    isset($_POST["{$key}_can_delete"]) ? 1 : 0,
                ]);
            }
        }

        // Invalidate all sessions' permission caches
        clearPermissionsCache();

        $_SESSION['flash_success'] = 'Permisos actualizados correctamente.';
        header('Location: settings.php');
        exit;
    } catch (Exception $e) {
        $error = 'Error al guardar los permisos. Verifica que la tabla exista.';
    }
}

// ── Load current permissions ──
$perms = [];
try {
    $stmt = $db->query("SELECT * FROM permissions");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $perms[$row['module']][$row['role']] = $row;
    }
} catch (Exception $e) {
    $error = 'La tabla de permisos no existe. Ejecuta <code>permissions_migration.sql</code> primero.';
}

// Helper to check if a permission is set (with fallback defaults)
function perm(array $perms, string $module, string $role, string $action): bool {
    return (bool)($perms[$module][$role][$action] ?? 0);
}
?>
<?php $page_title = 'Configuración'; $extra_css = ['../../public/css/settings.css']; include __DIR__ . '/../components/header.php'; ?>
<body>

  <?php $active_page = 'settings'; include __DIR__ . '/../components/sidebar.php'; ?>

  <main class="main-content">

    <?php
      $topbar_title    = '<span>Configuración</span> del sistema';
      $topbar_subtitle = 'Gestiona permisos y accesos por rol';
      $topbar_action   = '';
      include __DIR__ . '/../components/topbar.php'; ?>

    <div class="settings-layout">

      <?php if ($success): ?>
      <div class="alert alert-success">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        <?= htmlspecialchars($success) ?>
      </div>
      <?php endif; ?>

      <?php if ($error): ?>
      <div class="alert alert-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= $error ?>
      </div>
      <?php endif; ?>

      <!-- Info banner -->
      <div class="info-banner">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="8"/><line x1="12" y1="12" x2="12" y2="16"/>
        </svg>
        <div>
          <strong>Permisos de administrador</strong> — El rol <em>Administrador</em> siempre tiene acceso total a todos los módulos y no puede restringirse.
        </div>
      </div>

      <form method="POST" id="permissionsForm">
        <input type="hidden" name="action" value="save_permissions" />

        <div class="perm-sections-grid">
        <?php foreach ($modules as $module): ?>
        <section class="perm-section">

          <!-- Module header -->
          <div class="perm-module-header">
            <div class="module-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <?= $module_icons[$module] ?>
              </svg>
            </div>
            <h3><?= $module_labels[$module] ?></h3>
          </div>

          <!-- Permission grid -->
          <div class="perm-grid">

            <!-- Header row -->
            <div class="perm-row perm-header">
              <div class="perm-role-col"></div>
              <?php foreach ($action_labels as $action => $label): ?>
              <div class="perm-action-col"><?= $label ?></div>
              <?php endforeach; ?>
            </div>

            <!-- Admin row (always full, read-only) -->
            <div class="perm-row perm-row--admin">
              <div class="perm-role-col">
                <span class="role-badge role-admin">Administrador</span>
              </div>
              <div class="perm-row-actions">
              <?php foreach ($actions as $action): ?>
              <div class="perm-action-col">
                <span class="action-label-mobile"><?= $action_labels[$action] ?></span>
                <div class="toggle toggle--on toggle--disabled" title="Siempre activo">
                  <div class="toggle-knob"></div>
                </div>
              </div>
              <?php endforeach; ?>
              </div>
            </div>

            <!-- Supervisor & Empleado rows -->
            <?php foreach ($roles as $role): ?>
            <?php $key = "{$module}_{$role}"; ?>
            <div class="perm-row">
              <div class="perm-role-col">
                <span class="role-badge role-<?= $role ?>"><?= $role_labels[$role] ?></span>
              </div>

              <div class="perm-row-actions">
              <?php foreach ($actions as $action): ?>
              <?php
                $checked  = perm($perms, $module, $role, $action);
                $inputKey = "{$key}_{$action}";
              ?>
              <div class="perm-action-col">
                <span class="action-label-mobile"><?= $action_labels[$action] ?></span>
                <label class="toggle <?= $checked ? 'toggle--on' : '' ?>" data-key="<?= $inputKey ?>">
                  <input type="checkbox"
                    name="<?= $inputKey ?>"
                    <?= $checked ? 'checked' : '' ?>
                    class="toggle-input"
                    data-module="<?= $module ?>"
                    data-role="<?= $role ?>"
                    data-action="<?= $action ?>"
                  />
                  <div class="toggle-knob"></div>
                </label>
              </div>
              <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>

          </div><!-- /perm-grid -->
        </section>
        <?php endforeach; ?>
        </div><!-- /perm-sections-grid -->

        <div class="settings-footer">
          <p class="unsaved-hint" id="unsavedHint" style="display:none">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Tienes cambios sin guardar
          </p>
          <button type="submit" class="btn-primary" id="saveBtn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
              <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
            </svg>
            Guardar cambios
          </button>
        </div>

      </form>
    </div>
  </main>

<?php $extra_js = ['../../public/js/settings.js'];
  include __DIR__ . '/../components/footer.php'; ?>