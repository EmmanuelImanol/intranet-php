<?php
/**
 * Reusable sidebar partial.
 * Requires: $user_name, $user_role, $user_area (optional), $active_page
 * $active_page values: 'dashboard' | 'news' | 'documents' | 'users' | 'profile' | 'settings'
 */

$role_labels_sidebar = [
    'admin'      => 'Administrador',
    'supervisor' => 'Supervisor',
    'empleado'   => 'Empleado',
];
$role_label_sidebar = $role_labels_sidebar[$user_role] ?? ucfirst($user_role);

// Depth: how many folders deep from the current file to root
// pages/ = 2 levels up  →  ../../
$base = isset($sidebar_base) ? $sidebar_base : '../../';
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="logo-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2">
        <rect x="3" y="3" width="7" height="7" rx="1"/>
        <rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/>
        <rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
    </div>
    <span class="logo-text">Intranet</span>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Principal</div>

    <a href="<?= $base ?>src/pages/dashboard.php"
       class="nav-item <?= ($active_page ?? '') === 'dashboard' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7" rx="1"/>
        <rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/>
        <rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      Dashboard
    </a>

    <a href="<?= $base ?>src/pages/news.php"
       class="nav-item <?= ($active_page ?? '') === 'news' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 22h16a2 2 0 002-2V4a2 2 0 00-2-2H8a2 2 0 00-2 2v16a2 2 0 002 2z"/>
        <path d="M8 6h8M8 10h8M8 14h4"/>
      </svg>
      Noticias
    </a>

    <a href="<?= $base ?>src/pages/documents.php"
       class="nav-item <?= ($active_page ?? '') === 'documents' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
      </svg>
      Documentos
    </a>

    <a href="<?= $base ?>src/pages/profile.php"
       class="nav-item <?= ($active_page ?? '') === 'profile' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      Mi perfil
    </a>

    <?php if (in_array($user_role, ['admin', 'supervisor'])): ?>
    <div class="nav-section-label" style="margin-top:1.5rem;">Gestión</div>

    <a href="<?= $base ?>src/pages/users.php"
       class="nav-item <?= ($active_page ?? '') === 'users' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <?php if ($user_role === 'admin'): ?>
        <path d="M23 21v-2a4 4 0 00-3-3.87"/>
        <path d="M16 3.13a4 4 0 010 7.75"/>
        <?php endif; ?>
      </svg>
      <?= $user_role === 'admin' ? 'Usuarios' : 'Mi equipo' ?>
    </a>
    <?php endif; ?>

    <?php if ($user_role === 'admin'): ?>
    <div class="nav-section-label" style="margin-top:1.5rem;">Sistema</div>
    <a href="<?= $base ?>src/pages/settings.php"
       class="nav-item <?= ($active_page ?? '') === 'settings' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="3"/>
        <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
      </svg>
      Configuración
    </a>
    <?php endif; ?>

  </nav>

  <!-- Area badge for non-admins -->
  <?php if (!empty($user_area) && $user_role !== 'admin'): ?>
  <?php
    $area_labels_sidebar = [
      'administracion_finanzas' => 'Adm. y Finanzas',
      'comercial'               => 'Comercial',
      'controller'              => 'Controller',
      'culinary_innovacion'     => 'Culinary & Inn.',
      'direccion_general'       => 'Dirección General',
      'finanzas'                => 'Finanzas',
      'fiscal'                  => 'Fiscal',
      'mantenimiento'           => 'Mantenimiento',
      'recursos_humanos'        => 'Recursos Humanos',
      'restaurantes'            => 'Restaurantes',
      'operaciones'             => 'Operaciones',
    ];
    $area_display = $area_labels_sidebar[$user_area] ?? ucfirst($user_area);
  ?>
  <div class="sidebar-area-badge">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
      <polyline points="9 22 9 12 15 12 15 22"/>
    </svg>
    <?= htmlspecialchars($area_display) ?>
  </div>
  <?php endif; ?>

  <div class="sidebar-footer">
    <div class="user-badge">
      <div class="user-avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
      <div class="user-info">
        <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
        <span class="user-role"><?= $role_label_sidebar ?></span>
      </div>
    </div>
    <div style="display:flex;gap:0.4rem;align-items:center;">
      <button id="themeToggle" title="Cambiar tema">
        <svg id="iconSun" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        <svg id="iconMoon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
      </button>
      <a href="<?= $base ?>logout.php" class="btn-logout" title="Cerrar sesión">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      </a>
    </div>
  </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
