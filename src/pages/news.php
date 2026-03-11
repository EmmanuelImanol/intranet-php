<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];
$user_area = $_SESSION['user_area'] ?? '';
$db        = connectDB();
loadPermissions($db);

$error   = '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);
$modal_open = false;

$can_manage = can('noticias', 'create');

// ── Create ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($title) || empty($content)) {
        $error = 'El título y el contenido son obligatorios.';
        $modal_open = true;
    } else {
        $stmt = $db->prepare("INSERT INTO news (title, content, user_id, status) VALUES (?, ?, ?, 'published')");
        $stmt->execute([$title, $content, $user_id]);
        $_SESSION['flash_success'] = 'Noticia publicada correctamente.';
        header('Location: news.php');
        exit;
    }
}

// ── Edit ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $edit_id = (int) $_POST['edit_id'];
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($title) || empty($content)) {
        $error = 'El título y el contenido son obligatorios.';
        $modal_open = true;
    } else {
        $stmt = $db->prepare("UPDATE news SET title = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $content, $edit_id]);
        $_SESSION['flash_success'] = 'Noticia actualizada correctamente.';
        header('Location: news.php');
        exit;
    }
}

// ── Archive / Restore ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $toggle_id = (int) $_POST['toggle_id'];
    $new_status = $_POST['new_status'] === 'archived' ? 'archived' : 'published';
    $stmt = $db->prepare("UPDATE news SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $toggle_id]);
    $label = $new_status === 'archived' ? 'archivada' : 'publicada';
    $_SESSION['flash_success'] = "Noticia {$label} correctamente.";
    header('Location: news.php');
    exit;
}

// ── Fetch news ──
$filter_status = $_GET['status'] ?? 'published';
$search        = trim($_GET['search'] ?? '');

$sql    = "SELECT n.*, u.name AS author FROM news n LEFT JOIN users u ON n.user_id = u.id WHERE n.status = ?";
$params = [$filter_status];

if ($search) {
    $sql .= " AND (n.title LIKE ? OR n.content LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql .= " ORDER BY n.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$news_list = $stmt->fetchAll();

// ── Fetch single news to edit ──
$edit_news = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $edit_news = $stmt->fetch();
}

$role_labels = ['admin' => 'Administrador', 'supervisor' => 'Supervisor', 'empleado' => 'Empleado'];
$role_label  = $role_labels[$user_role] ?? ucfirst($user_role);
?>
<?php $page_title = 'Noticias'; $extra_css = ['../../public/css/news.css']; include __DIR__ . '/../components/head.php'; ?>
<body>

  <!-- ── Sidebar ── -->
  <?php $active_page = 'news'; include __DIR__ . '/../components/sidebar.php'; ?>

  <!-- ── Main ── -->
  <main class="main-content">

<?php
  $topbar_title    = 'Panel de <span>Noticias</span>';
  $topbar_subtitle = count($news_list) . ' noticia(s) ' . ($filter_status === 'archived' ? 'archivadas' : 'publicadas');
  ob_start(); ?>
  <?php if ($can_manage): ?>
      <button class="btn-primary" id="btnOpenModal">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nueva noticia
      </button>
      <?php endif; ?>
  <?php $topbar_action = ob_get_clean();
  include __DIR__ . '/../components/topbar.php'; ?>

    <!-- Success alert -->
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="toolbar">
      <form method="GET" class="search-form">
        <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>" />
        <div class="search-wrapper">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" name="search" placeholder="Buscar noticias..." value="<?= htmlspecialchars($search) ?>" />
        </div>
      </form>
      <div class="filter-bar">
        <a href="news.php?status=published" class="filter-chip <?= $filter_status === 'published' ? 'active' : '' ?>">
          <span class="status-dot published"></span> Publicadas
        </a>
        <a href="news.php?status=archived" class="filter-chip <?= $filter_status === 'archived' ? 'active' : '' ?>">
          <span class="status-dot archived"></span> Archivadas
        </a>
      </div>
    </div>

    <!-- News feed -->
    <div class="news-container">
      <?php if (empty($news_list)): ?>
        <div class="empty-state">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 22h16a2 2 0 002-2V4a2 2 0 00-2-2H8a2 2 0 00-2 2v16a2 2 0 002 2z"/><path d="M8 6h8M8 10h8M8 14h4"/></svg>
          <p>No hay noticias <?= $filter_status === 'archived' ? 'archivadas' : 'publicadas' ?> aún.</p>
        </div>
      <?php else: ?>
        <?php foreach ($news_list as $i => $item): ?>
        <article class="news-card" style="--delay: <?= $i * 0.06 ?>s">
          <div class="news-card-header">
            <div class="news-meta">
              <div class="news-author-avatar"><?= strtoupper(substr($item['author'] ?? 'A', 0, 1)) ?></div>
              <div>
                <span class="news-author"><?= htmlspecialchars($item['author'] ?? 'Desconocido') ?></span>
                <span class="news-date"><?= date('d M Y · H:i', strtotime($item['created_at'])) ?></span>
              </div>
            </div>
            <div class="news-card-actions">
              <span class="status-badge <?= $item['status'] ?>">
                <?= $item['status'] === 'published' ? 'Publicada' : 'Archivada' ?>
              </span>
              <?php if ($can_manage): ?>
              <a href="?edit=<?= $item['id'] ?><?= $filter_status !== 'published' ? '&status=' . $filter_status : '' ?>"
                 class="btn-action view" title="Editar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </a>
              <form method="POST">
                <input type="hidden" name="action"     value="toggle_status" />
                <input type="hidden" name="toggle_id"  value="<?= $item['id'] ?>" />
                <input type="hidden" name="new_status" value="<?= $item['status'] === 'published' ? 'archived' : 'published' ?>" />
                <button type="submit" class="btn-action <?= $item['status'] === 'published' ? 'archive' : 'restore' ?>"
                  title="<?= $item['status'] === 'published' ? 'Archivar' : 'Restaurar' ?>">
                  <?php if ($item['status'] === 'published'): ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
                  <?php else: ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                  <?php endif; ?>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </div>
          <div class="news-card-body" id="card-<?= $item['id'] ?>">
            <h2 class="news-title"><?= htmlspecialchars($item['title']) ?></h2>
            <div class="news-content"><?= nl2br(htmlspecialchars($item['content'])) ?></div>
          </div>
          <?php if ($item['updated_at']): ?>
          <div class="news-card-footer">
            Editada el <?= date('d M Y · H:i', strtotime($item['updated_at'])) ?>
          </div>
          <?php endif; ?>
        </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </main>

  <!-- ── Create / Edit Modal ── -->
  <div class="modal-overlay <?= ($edit_news || $modal_open) ? 'open' : '' ?>" id="modalOverlay">
    <div class="modal modal-news">
      <div class="modal-header">
        <h3><?= $edit_news ? 'Editar noticia' : 'Nueva noticia' ?></h3>
        <a href="news.php" class="modal-close">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </a>
      </div>
      <form method="POST" class="modal-form">
        <input type="hidden" name="action"  value="<?= $edit_news ? 'edit' : 'create' ?>" />
        <?php if ($edit_news): ?>
          <input type="hidden" name="edit_id" value="<?= $edit_news['id'] ?>" />
        <?php endif; ?>

        <?php if ($error && $modal_open): ?>
          <div class="modal-error">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <div class="field-group">
          <label>Título</label>
          <input type="text" name="title" placeholder="Título de la noticia" required
            value="<?= htmlspecialchars($edit_news['title'] ?? $_POST['title'] ?? '') ?>" />
        </div>

        <div class="field-group">
          <label>Contenido</label>
          <textarea name="content" rows="8" placeholder="Escribe el contenido de la noticia..." required><?= htmlspecialchars($edit_news['content'] ?? $_POST['content'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn-primary" style="width:100%; justify-content:center;">
          <?= $edit_news ? 'Guardar cambios' : 'Publicar noticia' ?>
        </button>
      </form>
    </div>
  </div>
<?php $extra_js = ['../../public/js/news.js'];
  include __DIR__ . '/../components/footer.php'; ?>
