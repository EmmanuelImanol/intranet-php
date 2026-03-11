<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permissions.php';

// Protect route — redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Role label
$role_labels = [
    'admin'      => 'Administrador',
    'supervisor' => 'Supervisor',
    'empleado'   => 'Empleado',
];
$role_label = $role_labels[$user_role] ?? ucfirst($user_role);
$user_id    = $_SESSION['user_id'];
$user_area  = $_SESSION['user_area'] ?? '';
$is_admin   = $user_role === 'admin';

$db = connectDB();
loadPermissions($db);

// ── Stat: total users (admin = all, supervisor = their area, empleado = their area) ──
if ($is_admin) {
    $stmt = $db->query("SELECT COUNT(*) FROM users");
} else {
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE area = ?");
    $stmt->execute([$user_area]);
}
$total_users = (int) $stmt->fetchColumn();

// ── Stat: published news ──
$stmt = $db->query("SELECT COUNT(*) FROM news WHERE status = 'published'");
$total_news = (int) $stmt->fetchColumn();

// ── Stat: documents visible to this user ──
if ($is_admin) {
    $stmt = $db->query("SELECT COUNT(*) FROM documents WHERE deleted_at IS NULL");
} elseif (!empty($user_area)) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM documents WHERE deleted_at IS NULL AND area = ?");
    $stmt->execute([$user_area]);
} else {
    $stmt = $db->prepare("SELECT COUNT(*) FROM documents WHERE deleted_at IS NULL AND user_id = ?");
    $stmt->execute([$user_id]);
}
$total_docs = (int) $stmt->fetchColumn();

// ── Stat: documents uploaded this month ──
if ($is_admin) {
    $stmt = $db->query("SELECT COUNT(*) FROM documents WHERE deleted_at IS NULL AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
} elseif (!empty($user_area)) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM documents WHERE deleted_at IS NULL AND area = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
    $stmt->execute([$user_area]);
} else {
    $stmt = $db->prepare("SELECT COUNT(*) FROM documents WHERE deleted_at IS NULL AND user_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
    $stmt->execute([$user_id]);
}
$docs_this_month = (int) $stmt->fetchColumn();

// ── Latest published news ──
$news = [];
$stmt = $db->query("SELECT n.*, u.name AS author FROM news n LEFT JOIN users u ON n.user_id = u.id WHERE n.status = 'published' ORDER BY n.created_at DESC LIMIT 5");
if ($stmt) $news = $stmt->fetchAll();

// ── Recent documents (area-aware) ──
$docs = [];
if ($is_admin) {
    $stmt = $db->query("SELECT d.*, u.name AS author FROM documents d LEFT JOIN users u ON d.user_id = u.id WHERE d.deleted_at IS NULL ORDER BY d.created_at DESC LIMIT 5");
} elseif (!empty($user_area)) {
    $stmt = $db->prepare("SELECT d.*, u.name AS author FROM documents d LEFT JOIN users u ON d.user_id = u.id WHERE d.deleted_at IS NULL AND d.area = ? GROUP BY d.id ORDER BY d.created_at DESC LIMIT 5");
    $stmt->execute([$user_area]);
} else {
    $stmt = $db->prepare("SELECT d.*, u.name AS author FROM documents d LEFT JOIN users u ON d.user_id = u.id WHERE d.deleted_at IS NULL AND d.user_id = ? GROUP BY d.id ORDER BY d.created_at DESC LIMIT 5");
    $stmt->execute([$user_id]);
}
if ($stmt) $docs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<?php $page_title = 'Dashboard'; include __DIR__ . '/../components/head.php'; ?>
<body>

  <!-- ── Sidebar ── -->
  <?php $active_page = 'dashboard'; include __DIR__ . '/../components/sidebar.php'; ?>

  <!-- ── Main content ── -->
  <main class="main-content">

<?php
  $topbar_title    = 'Bienvenido, <span>' . htmlspecialchars(explode(' ', $user_name)[0]) . '</span>';
  $topbar_subtitle = date('l, d \de F \de Y');
  ob_start(); ?>
  <div class="topbar-badge"><?= $role_label ?></div>
  <?php $topbar_action = ob_get_clean();
  include __DIR__ . '/../components/topbar.php'; ?>

    <!-- Stats cards -->
    <section class="stats-grid">
      <a href="users.php" class="stat-card" style="--delay:0s">
        <div class="stat-icon indigo">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <div class="stat-info">
          <span class="stat-value"><?= $total_users ?></span>
          <span class="stat-label"><?= $is_admin ? "Usuarios totales" : "Usuarios en tu área" ?></span>
        </div>
      </div>
      <a href="news.php" class="stat-card" style="--delay:0.08s">
        <div class="stat-icon pink">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 22h16a2 2 0 002-2V4a2 2 0 00-2-2H8a2 2 0 00-2 2v16a2 2 0 002 2z"/></svg>
        </div>
        <div class="stat-info">
          <span class="stat-value"><?= $total_news ?></span>
          <span class="stat-label">Noticias publicadas</span>
        </div>
      </div>
      <a href="documents.php" class="stat-card" style="--delay:0.16s">
        <div class="stat-icon teal">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="stat-info">
          <span class="stat-value"><?= $total_docs ?></span>
          <span class="stat-label"><?= $is_admin ? "Documentos totales" : "Documentos de tu área" ?></span>
        </div>
      </div>
      <a href="documents.php" class="stat-card" style="--delay:0.24s">
        <div class="stat-icon amber">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-info">
          <span class="stat-value"><?= $docs_this_month ?></span>
          <span class="stat-label">Docs subidos este mes</span>
        </div>
      </a></section>

    <!-- Content grid -->
    <section class="content-grid">

      <!-- News panel -->
      <div class="panel" style="--delay:0.1s">
        <div class="panel-header">
          <h2>Noticias recientes</h2>
          <a href="news.php" class="panel-link">Ver todas →</a>
        </div>
        <div class="panel-body">
          <?php if (empty($news)): ?>
            <div class="empty-state">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 22h16a2 2 0 002-2V4a2 2 0 00-2-2H8a2 2 0 00-2 2v16a2 2 0 002 2z"/></svg>
              <p>No hay noticias publicadas aún</p>
            </div>
          <?php else: ?>
            <?php foreach ($news as $item): ?>
            <a href="news.php" class="news-item">
              <div class="news-dot"></div>
              <div>
                <p class="news-title"><?= htmlspecialchars($item['title']) ?></p>
                <span class="news-date"><?= htmlspecialchars($item['author'] ?? '—') ?> · <?= date('d M Y', strtotime($item['created_at'])) ?></span>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Documents panel -->
      <div class="panel" style="--delay:0.2s">
        <div class="panel-header">
          <h2>Documentos recientes</h2>
          <a href="documents.php" class="panel-link">Ver todos →</a>
        </div>
        <div class="panel-body">
          <?php if (empty($docs)): ?>
            <div class="empty-state">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              <p>No hay documentos subidos aún</p>
            </div>
          <?php else: ?>
            <?php foreach ($docs as $doc): ?>
            <a href="documents.php?view=<?= $doc['id'] ?>" class="doc-item">
              <div class="doc-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              </div>
              <div>
                <p class="doc-name"><?= htmlspecialchars($doc['name']) ?></p>
                <span class="doc-date"><?= htmlspecialchars($doc['author'] ?? '—') ?> · <?= date('d M Y', strtotime($doc['created_at'])) ?></span>
              </div>
            </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </section>
  </main>
<?php
  include __DIR__ . '/../components/footer.php'; ?>
