<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permissions.php';

// Protect route
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];
$user_area = $_SESSION['user_area'] ?? '';
$is_admin  = $user_role === 'admin';
$db        = connectDB();

$error   = '';
$success = '';

// ── Upload document ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $file     = $_FILES['document'];
    $area     = trim($_POST['area'] ?? 'general');
    $doc_name = trim($_POST['doc_name'] ?? $file['name']);

    $allowed_types = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg', 'image/png', 'text/plain',
        'video/mp4', 'video/webm', 'video/ogg',
    ];
    $max_size = 500 * 1024 * 1024; // 500MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Error al subir el archivo.';
    } elseif ($file['size'] > $max_size) {
        $error = 'El archivo supera el límite de 10MB.';
    } elseif (!in_array($file['type'], $allowed_types)) {
        $error = 'Tipo de archivo no permitido.';
    } else {
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('doc_') . '.' . $ext;
        $dest     = __DIR__ . '/../../public/uploads/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $stmt = $db->prepare("INSERT INTO documents (name, file_path, user_id, area) VALUES (?, ?, ?, ?)");
            $stmt->execute([$doc_name, $filename, $user_id, $area]);
            $success = 'Documento subido correctamente.';
        } else {
            $error = 'No se pudo guardar el archivo. Verifica permisos de la carpeta uploads.';
        }
    }
}

// ── Soft delete ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (can('documentos', 'delete')) {
        $delete_id = (int) $_POST['delete_id'];
        $stmt = $db->prepare("UPDATE documents SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$delete_id]);
        $success = 'Documento eliminado correctamente.';
    }
}

// ── Fetch documents (only not soft-deleted) ──
$filter_area = $_GET['area'] ?? '';
$search      = trim($_GET['search'] ?? '');

$sql    = "SELECT d.*, u.name AS author FROM documents d LEFT JOIN users u ON d.user_id = u.id WHERE d.deleted_at IS NULL";
$params = [];

// Non-admins only see documents from their own area
if (!$is_admin && !empty($user_area)) {
    $sql .= " AND d.area = ?";
    $params[] = $user_area;
} elseif (!$is_admin && empty($user_area)) {
    $sql .= " AND d.user_id = ?";
    $params[] = $user_id;
}

if ($filter_area) {
    $sql .= " AND d.area = ?";
    $params[] = $filter_area;
}
if ($search) {
    $sql .= " AND d.name LIKE ?";
    $params[] = '%' . $search . '%';
}

$sql .= " ORDER BY d.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll();

// ── Fetch distinct areas ──
if ($is_admin) {
    $areas_stmt = $db->query("SELECT DISTINCT area FROM documents WHERE area IS NOT NULL AND deleted_at IS NULL ORDER BY area");
    $areas = $areas_stmt->fetchAll(PDO::FETCH_COLUMN);
} else {
    $areas = !empty($user_area) ? [$user_area] : [];
}

$role_labels = ['admin' => 'Administrador', 'supervisor' => 'Supervisor', 'empleado' => 'Empleado'];
$role_label  = $role_labels[$user_role] ?? ucfirst($user_role);

// Auto-open preview if ?view=ID is passed
$auto_view = null;
if (!empty($_GET['view'])) {
    $stmt = $db->prepare("SELECT * FROM documents WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([(int) $_GET['view']]);
    $auto_view = $stmt->fetch();
}

// ── File icon helper ──
function getFileIcon(string $filename): string {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return match($ext) {
        'pdf'              => 'pdf',
        'doc', 'docx'      => 'word',
        'xls', 'xlsx'      => 'excel',
        'jpg', 'jpeg', 'png' => 'image',
        'mp4', 'webm', 'ogg' => 'video',
        default            => 'file',
    };
}

// ── File size helper ──
function getFileSize(string $filename): string {
    $path = __DIR__ . '/../../public/uploads/' . $filename;
    if (!file_exists($path)) return '—';
    $bytes = filesize($path);
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

// ── Viewable types (PDF + images) ──
function isViewable(string $filename): bool {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'mp4', 'webm', 'ogg']);
}
?>
<?php $page_title = 'Documentos'; $extra_css = ['../../public/css/documents.css']; include __DIR__ . '/../components/head.php'; ?>
<body>

  <!-- ── Sidebar ── -->
  <?php $active_page = 'documents'; include __DIR__ . '/../components/sidebar.php'; ?>

  <!-- ── Main ── -->
  <main class="main-content">

<?php
  $topbar_title    = 'Gestión de <span>Documentos</span>';
  $topbar_subtitle = count($documents) . ' documento(s) encontrados';
  ob_start(); ?>
  <button class="btn-primary" id="btnUpload">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Subir archivo
      </button>
  <?php $topbar_action = ob_get_clean();
  include __DIR__ . '/../components/topbar.php'; ?>

    <!-- Alerts -->
    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Search + Filter bar -->
    <div class="toolbar">
      <form method="GET" class="search-form">
        <?php if ($filter_area): ?>
          <input type="hidden" name="area" value="<?= htmlspecialchars($filter_area) ?>" />
        <?php endif; ?>
        <div class="search-wrapper">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" name="search" placeholder="Buscar documento..." value="<?= htmlspecialchars($search) ?>" />
        </div>
      </form>
      <div class="filter-bar">
        <?php if (can('documentos', 'delete')): ?>
        <a href="documents.php" class="filter-chip <?= !$filter_area ? 'active' : '' ?>">Todos</a>
        <?php foreach ($areas as $area): ?>
          <a href="?area=<?= urlencode($area) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
             class="filter-chip <?= $filter_area === $area ? 'active' : '' ?>">
            <?= htmlspecialchars(ucfirst($area)) ?>
          </a>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Documents table -->
    <div class="table-wrapper">
      <?php if (empty($documents)): ?>
        <div class="empty-state">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <p>No hay documentos disponibles.</p>
        </div>
      <?php else: ?>
        <table class="doc-table">
          <thead>
            <tr>
              <th>Documento</th>
              <th>Área</th>
              <th>Tamaño</th>
              <th>Subido por</th>
              <th>Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($documents as $doc): ?>
            <?php $icon = getFileIcon($doc['file_path']); ?>
            <tr class="doc-row" onclick="openPreviewFromRow(this)"
                data-id="<?= $doc['id'] ?>"
                data-file="../../public/uploads/<?= urlencode($doc['file_path']) ?>"
                data-name="<?= htmlspecialchars($doc['name'], ENT_QUOTES) ?>"
                data-viewable="<?= (!empty($doc['file_path']) && isViewable($doc['file_path'])) ? 'true' : 'false' ?>">
              <td>
                <div class="doc-name-cell">
                  <div class="doc-type-icon <?= $icon ?>"><?= strtoupper(pathinfo($doc['file_path'], PATHINFO_EXTENSION)) ?></div>
                  <span><?= htmlspecialchars($doc['name']) ?></span>
                </div>
              </td>
              <td><span class="area-badge"><?= htmlspecialchars(ucfirst($doc['area'] ?? 'General')) ?></span></td>
              <td class="text-muted"><?= getFileSize($doc['file_path']) ?></td>
              <td class="text-muted"><?= htmlspecialchars($doc['author'] ?? 'Desconocido') ?></td>
              <td class="text-muted"><?= date('d M Y', strtotime($doc['created_at'])) ?></td>
              <td>
                <div class="action-btns">

                  <!-- Ver (PDF e imágenes) -->
                  <?php if (isViewable($doc['file_path'])): ?>
                  <button class="btn-action view" title="Ver documento"
                    onclick="openPreview('../../public/uploads/<?= urlencode($doc['file_path']) ?>',
                    '<?= htmlspecialchars($doc['name'], ENT_QUOTES) ?>')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <?php endif; ?>

                  <!-- Descargar -->
                  <a href="../../public/uploads/<?= urlencode($doc['file_path']) ?>"
                     download="<?= htmlspecialchars($doc['name']) ?>"
                     class="btn-action download" title="Descargar">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                  </a>

                  <!-- Borrado lógico (solo admin/supervisor) -->
                  <?php if (can('documentos', 'create')): ?>
                  <button type="button" class="btn-action delete" title="Eliminar"
                    onclick="openDeleteDocModal(<?= $doc['id'] ?>, '<?= htmlspecialchars($doc['name'], ENT_QUOTES) ?>')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                  </button>
                  <?php endif; ?>

                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- ── Inline document preview ── -->
    <div class="preview-panel" id="previewPanel">
      <div class="preview-header">
        <div class="preview-title-group">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          <span id="previewTitle">Vista previa</span>
        </div>
      </div>
      <div class="preview-body" id="previewBody">
        <div class="preview-placeholder">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="12" y2="17"/></svg>
          <p>Selecciona un documento para previsualizarlo</p>
          <span>Haz clic en cualquier fila o en el botón <strong>ver</strong></span>
        </div>
      </div>
    </div>

  </main>

  <!-- ── Upload Modal ── -->
  <div class="modal-overlay" id="modalOverlay">
    <div class="modal">
      <div class="modal-header">
        <h3>Subir documento</h3>
        <button class="modal-close" id="btnCloseModal">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
      <form method="POST" enctype="multipart/form-data" class="modal-form">
        <div class="field-group">
          <label>Nombre del documento</label>
          <input type="text" name="doc_name" placeholder="Ej: Manual de bienvenida" required />
        </div>
        <div class="field-group">
          <label>Área</label>
          <select name="area">
            <option value="administracion_finanzas">Administración y Finanzas</option>
            <option value="comercial">Comercial</option>
            <option value="controller">Controller</option>
            <option value="culinary_innovacion">Culinary & Innovación</option>
            <option value="direccion_general">Dirección General</option>
            <option value="finanzas">Finanzas</option>
            <option value="fiscal">Fiscal</option>
            <option value="mantenimiento">Mantenimiento</option>
            <option value="recursos_humanos">Recursos Humanos</option>
            <option value="restaurantes">Restaurantes</option>
            <option value="operaciones">Operaciones</option>
          </select>
        </div>
        <div class="field-group">
          <label>Archivo</label>
          <div class="file-drop" id="fileDrop">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <p>Arrastra tu archivo aquí o <span>selecciona</span></p>
            <small>PDF, Word, Excel, imágenes, videos (mp4, webm) — máx. 500MB</small>
            <input type="file" name="document" id="fileInput" required />
          </div>
          <p class="file-name" id="fileName"></p>
        </div>
        <button type="submit" class="btn-primary" style="width:100%; justify-content:center;">
          Subir documento
        </button>
      </form>
    </div>
  </div>

  <!-- ── Delete Document Modal ── -->
  <div class="modal-overlay" id="deleteDocOverlay">
    <div class="modal modal-sm">
      <div class="delete-modal-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
      </div>
      <h3 class="delete-modal-title">Eliminar documento</h3>
      <p class="delete-modal-text">¿Estás seguro de que deseas eliminar <strong id="deleteDocName"></strong>? Esta acción no se puede deshacer.</p>
      <div class="delete-modal-actions">
        <button class="btn-cancel" onclick="closeDeleteDocModal()">Cancelar</button>
        <form method="POST" id="deleteDocForm">
          <input type="hidden" name="delete_id" id="deleteDocId" />
          <button type="submit" class="btn-danger">Sí, eliminar</button>
        </form>
      </div>
    </div>
  </div>

  <?php if ($auto_view): ?>
  <script>
    window.__autoView = {
      id:       <?= (int) $auto_view['id'] ?>,
      filePath: '../../public/uploads/<?= urlencode($auto_view['file_path']) ?>',
      name:     <?= json_encode($auto_view['name']) ?>
    };
  </script>
  <?php endif; ?>
<?php $extra_js = ['../../public/js/documents.js'];
  include __DIR__ . '/../components/footer.php'; ?>
