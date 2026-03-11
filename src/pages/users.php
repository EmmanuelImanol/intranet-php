<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permissions.php';

// Only admins and supervisors can access this page
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$current_user_id   = $_SESSION['user_id'];
$user_name         = $_SESSION['user_name'];
$user_role         = $_SESSION['user_role'];
$user_area         = $_SESSION['user_area'] ?? '';
$is_admin          = $user_role === 'admin';
$db                = connectDB();
loadPermissions($db);
requirePerm('usuarios', 'view', 'dashboard.php');

// Helper — consistent color per user initial
function getUserColor(string $name): string {
    $colors = ['#6366f1','#ec4899','#14b8a6','#f59e0b','#3b82f6','#8b5cf6','#ef4444','#10b981'];
    return $colors[ord(strtoupper($name[0])) % count($colors)];
}

$error       = '';
$success     = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);
$modal_open  = false; // keeps modal open on validation error

// ── Create user ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = $is_admin ? trim($_POST['role'] ?? 'empleado') : 'empleado';
    $area     = $is_admin ? trim($_POST['area'] ?? '') : $user_area;

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Nombre, email y contraseña son obligatorios.';
        $modal_open = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no tiene un formato válido.';
        $modal_open = true;
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
        $modal_open = true;
    } else {
        // Check duplicate email
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Ya existe un usuario con ese correo.';
            $modal_open = true;
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, area) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hash, $role, $area]);
            $success = "Usuario {$name} creado correctamente.";
        }
    }
}

// ── Edit user ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $edit_id  = (int) $_POST['edit_id'];
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = $is_admin ? trim($_POST['role'] ?? 'empleado') : 'empleado';
    $area     = $is_admin ? trim($_POST['area'] ?? '') : $user_area;
    $password = trim($_POST['password'] ?? '');

    // Supervisors cannot edit admins or other supervisors
    if (!$is_admin) {
        $stmt = $db->prepare("SELECT role, area FROM users WHERE id = ?");
        $stmt->execute([$edit_id]);
        $target = $stmt->fetch();
        if (!$target || $target['role'] !== 'empleado' || $target['area'] !== $user_area) {
            $_SESSION['flash_success'] = ''; // clear
            $error = 'No tienes permiso para editar este usuario.';
            header('Location: users.php');
            exit;
        }
    }

    if (empty($name) || empty($email)) {
        $error = 'Nombre y email son obligatorios.';
        $modal_open = true;
    } else {
        // Check duplicate email (excluding current user)
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $edit_id]);
        if ($stmt->fetch()) {
            $error = 'Ya existe otro usuario con ese correo.';
            $modal_open = true;
        } else {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE users SET name=?, email=?, role=?, area=?, password=? WHERE id=?");
                $stmt->execute([$name, $email, $role, $area, $hash, $edit_id]);
            } else {
                $stmt = $db->prepare("UPDATE users SET name=?, email=?, role=?, area=? WHERE id=?");
                $stmt->execute([$name, $email, $role, $area, $edit_id]);
            }
            $_SESSION['flash_success'] = "Usuario actualizado correctamente.";
            header('Location: users.php');
            exit;
        }
    }
}

// ── Hard delete ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delete_id = (int) $_POST['delete_id'];
    if ($delete_id === $current_user_id) {
        $error = 'No puedes eliminar tu propio usuario.';
    } else {
        if (!$is_admin) {
            $stmt = $db->prepare("SELECT role, area FROM users WHERE id = ?");
            $stmt->execute([$delete_id]);
            $target = $stmt->fetch();
            if (!$target || $target['role'] !== 'empleado' || $target['area'] !== $user_area) {
                header('Location: users.php');
                exit;
            }
        }
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success = 'Usuario eliminado correctamente.';
    }
}

// ── Fetch users ──
$search      = trim($_GET['search'] ?? '');
$filter_role = $_GET['role'] ?? '';

$sql    = "SELECT * FROM users WHERE 1=1";
$params = [];

// Supervisors only see their own area
if (!$is_admin) {
    $sql .= " AND area = ?";
    $params[] = $user_area;
}

if ($search) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
if ($filter_role) {
    $sql .= " AND role = ?";
    $params[] = $filter_role;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Fetch user to edit if requested
$edit_user = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $edit_user = $stmt->fetch();
    // Supervisors cannot edit admins, supervisors, or users outside their area
    if ($edit_user && !$is_admin) {
        if ($edit_user['role'] !== 'empleado' || $edit_user['area'] !== $user_area) {
            $edit_user = null;
        }
    }
}

$role_labels = ['admin' => 'Administrador', 'supervisor' => 'Supervisor', 'empleado' => 'Empleado'];
?>
<?php $page_title = 'Usuarios'; $extra_css = ['../../public/css/users.css']; include __DIR__ . '/../components/head.php'; ?>
<body>

  <!-- ── Sidebar ── -->
  <?php $active_page = 'users'; include __DIR__ . '/../components/sidebar.php'; ?>

  <!-- ── Main ── -->
  <main class="main-content">

<?php
  $topbar_title    = 'Gestión de <span>Usuarios</span>';
  $topbar_subtitle = count($users) . ' usuario(s) registrados';
  ob_start(); ?>
  <?php if ($is_admin || !empty($user_area)): ?>
      <button class="btn-primary" id="btnOpenModal">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nuevo usuario
      </button>
      <?php endif; ?>
  <?php $topbar_action = ob_get_clean();
  include __DIR__ . '/../components/topbar.php'; ?>

    <!-- Success alert (outside modal) -->
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Supervisor area notice -->
    <?php if (!$is_admin): ?>
    <div class="area-notice">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      Estás viendo únicamente los empleados de tu área.
    </div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="toolbar">
      <form method="GET" class="search-form">
        <?php if ($filter_role): ?>
          <input type="hidden" name="role" value="<?= htmlspecialchars($filter_role) ?>" />
        <?php endif; ?>
        <div class="search-wrapper">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" name="search" placeholder="Buscar por nombre o email..." value="<?= htmlspecialchars($search) ?>" />
        </div>
      </form>
      <div class="filter-bar">
        <a href="users.php" class="filter-chip <?= !$filter_role ? 'active' : '' ?>">Todos</a>
        <a href="?role=admin"      class="filter-chip <?= $filter_role === 'admin'      ? 'active' : '' ?>">Admins</a>
        <a href="?role=supervisor" class="filter-chip <?= $filter_role === 'supervisor' ? 'active' : '' ?>">Supervisores</a>
        <a href="?role=empleado"   class="filter-chip <?= $filter_role === 'empleado'   ? 'active' : '' ?>">Empleados</a>
      </div>
    </div>

    <!-- Users table -->
    <div class="table-wrapper">
      <?php if (empty($users)): ?>
        <div class="empty-state">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          <p>No se encontraron usuarios.</p>
        </div>
      <?php else: ?>
        <table class="doc-table">
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Email</th>
              <th>Rol</th>
              <th>Área</th>
              <th>Registro</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr class="<?= $u['id'] === $current_user_id ? 'current-user-row' : '' ?>">
              <td>
                <div class="user-cell">
                  <div class="user-avatar-sm" style="background: <?= getUserColor($u['name']) ?>">
                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                  </div>
                  <div>
                    <span class="user-cell-name"><?= htmlspecialchars($u['name']) ?></span>
                    <?php if ($u['id'] === $current_user_id): ?>
                      <span class="you-badge">Tú</span>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
              <td><span class="role-badge role-<?= $u['role'] ?>"><?= $role_labels[$u['role']] ?? $u['role'] ?></span></td>
              <td><?php if (!empty($u['area'])): ?><span class="area-badge area-<?= $u['area'] ?>"><?= strtoupper(htmlspecialchars($u['area'])) ?></span><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
              <td class="text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              <td>
                <div class="action-btns">
                  <?php $can_edit = $is_admin || ($u['role'] === 'empleado' && $u['area'] === $user_area); ?>
                  <?php if ($can_edit): ?>
                  <a href="?edit=<?= $u['id'] ?>" class="btn-action view" title="Editar">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </a>
                  <?php if ($u['id'] !== $current_user_id): ?>
                  <button type="button" class="btn-action delete" title="Eliminar"
                    onclick="openDeleteModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                  </button>
                  <?php endif; ?>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </main>

  <!-- ── Create / Edit Modal ── -->
  <div class="modal-overlay <?= ($edit_user || $modal_open) ? 'open' : '' ?>" id="modalOverlay">
    <div class="modal">
      <div class="modal-header">
        <h3><?= $edit_user ? 'Editar usuario' : 'Nuevo usuario' ?></h3>
        <a href="users.php" class="modal-close" id="btnCloseModal">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </a>
      </div>
      <form method="POST" class="modal-form">
        <input type="hidden" name="action"  value="<?= $edit_user ? 'edit' : 'create' ?>" />
        <?php if ($error && $modal_open): ?>
          <div class="modal-error">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
        <?php if ($edit_user): ?>
          <input type="hidden" name="edit_id" value="<?= $edit_user['id'] ?>" />
        <?php endif; ?>

        <div class="form-row">
          <div class="field-group">
            <label>Nombre completo</label>
            <input type="text" name="name" placeholder="Juan Pérez" required
              value="<?= htmlspecialchars($edit_user['name'] ?? '') ?>" />
          </div>
          <div class="field-group">
            <label>Correo electrónico</label>
            <input type="email" name="email" placeholder="usuario@empresa.com" required
              value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>" />
          </div>
        </div>

        <div class="form-row">
          <div class="field-group">
            <label>Rol</label>
            <?php if ($is_admin): ?>
            <select name="role">
              <option value="empleado"   <?= ($edit_user['role'] ?? '') === 'empleado'   ? 'selected' : '' ?>>Empleado</option>
              <option value="supervisor" <?= ($edit_user['role'] ?? '') === 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
              <option value="admin"      <?= ($edit_user['role'] ?? '') === 'admin'      ? 'selected' : '' ?>>Administrador</option>
            </select>
            <?php else: ?>
            <input type="text" value="Empleado" disabled />
            <input type="hidden" name="role" value="empleado" />
            <?php endif; ?>
          </div>
          <div class="field-group">
            <label>Área</label>
            <?php if ($is_admin): ?>
            <select name="area">
              <option value="">— Sin área —</option>
              <option value="administracion_finanzas" <?= ($edit_user['area'] ?? '') === 'administracion_finanzas' ? 'selected' : '' ?>>Administración y Finanzas</option>
              <option value="comercial" <?= ($edit_user['area'] ?? '') === 'comercial' ? 'selected' : '' ?>>Comercial</option>
              <option value="controller" <?= ($edit_user['area'] ?? '') === 'controller' ? 'selected' : '' ?>>Controller</option>
              <option value="culinary_innovacion" <?= ($edit_user['area'] ?? '') === 'culinary_innovacion' ? 'selected' : '' ?>>Culinary & Innovación</option>
              <option value="direccion_general" <?= ($edit_user['area'] ?? '') === 'direccion_general' ? 'selected' : '' ?>>Dirección General</option>
              <option value="finanzas" <?= ($edit_user['area'] ?? '') === 'finanzas' ? 'selected' : '' ?>>Finanzas</option>
              <option value="fiscal" <?= ($edit_user['area'] ?? '') === 'fiscal' ? 'selected' : '' ?>>Fiscal</option>
              <option value="mantenimiento" <?= ($edit_user['area'] ?? '') === 'mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
              <option value="recursos_humanos" <?= ($edit_user['area'] ?? '') === 'recursos_humanos' ? 'selected' : '' ?>>Recursos Humanos</option>
              <option value="restaurantes" <?= ($edit_user['area'] ?? '') === 'restaurantes' ? 'selected' : '' ?>>Restaurantes</option>
              <option value="operaciones" <?= ($edit_user['area'] ?? '') === 'operaciones' ? 'selected' : '' ?>>Operaciones</option>
            </select>
            <?php else: ?>
            <?php
              $area_labels = [
                'administracion_finanzas' => 'Administración y Finanzas',
                'comercial'    => 'Comercial', 'controller' => 'Controller',
                'culinary_innovacion' => 'Culinary & Innovación',
                'direccion_general' => 'Dirección General', 'finanzas' => 'Finanzas',
                'fiscal' => 'Fiscal', 'mantenimiento' => 'Mantenimiento',
                'recursos_humanos' => 'Recursos Humanos', 'restaurantes' => 'Restaurantes',
                'operaciones' => 'Operaciones',
              ];
            ?>
            <input type="text" value="<?= htmlspecialchars($area_labels[$user_area] ?? $user_area) ?>" disabled />
            <input type="hidden" name="area" value="<?= htmlspecialchars($user_area) ?>" />
            <?php endif; ?>
          </div>
        </div>

        <div class="field-group">
          <label><?= $edit_user ? 'Nueva contraseña (dejar vacío para no cambiar)' : 'Contraseña' ?></label>
          <div class="pass-wrapper">
            <input type="password" name="password" id="modalPassword"
              placeholder="<?= $edit_user ? 'Dejar vacío para mantener la actual' : 'Mínimo 6 caracteres' ?>"
              <?= $edit_user ? '' : 'required' ?> />
            <button type="button" class="toggle-pass" id="togglePass">
              <svg id="eyeIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-primary" style="width:100%; justify-content:center; margin-top:0.5rem;">
          <?= $edit_user ? 'Guardar cambios' : 'Crear usuario' ?>
        </button>
      </form>
    </div>
  </div>

  <!-- ── Delete Confirmation Modal ── -->
  <div class="modal-overlay" id="deleteOverlay">
    <div class="modal modal-sm">
      <div class="delete-modal-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
      </div>
      <h3 class="delete-modal-title">Eliminar usuario</h3>
      <p class="delete-modal-text">¿Estás seguro de que deseas eliminar a <strong id="deleteUserName"></strong>? Esta acción no se puede deshacer.</p>
      <div class="delete-modal-actions">
        <button class="btn-cancel" onclick="closeDeleteModal()">Cancelar</button>
        <form method="POST" id="deleteForm">
          <input type="hidden" name="action"    value="delete" />
          <input type="hidden" name="delete_id" id="deleteUserId" />
          <button type="submit" class="btn-danger">Sí, eliminar</button>
        </form>
      </div>
    </div>
  </div>
<?php $extra_js = ['../../public/js/users.js'];
  include __DIR__ . '/../components/footer.php'; ?>

