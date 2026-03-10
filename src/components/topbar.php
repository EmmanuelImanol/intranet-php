<?php
/**
 * Reusable topbar component.
 *
 * Required vars:
 *   $topbar_title    — string  e.g. 'Panel de <span>Noticias</span>'
 *   $topbar_subtitle — string  e.g. '12 noticias publicadas'
 *
 * Optional vars:
 *   $topbar_action   — string  raw HTML for the right-side action (button, badge, etc.)
 */
?>
<header class="topbar">
  <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="3" y1="6" x2="21" y2="6"/>
      <line x1="3" y1="12" x2="21" y2="12"/>
      <line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>
  <div class="topbar-greeting">
    <h1><?= $topbar_title ?? '' ?></h1>
    <p><?= $topbar_subtitle ?? '' ?></p>
  </div>
  <?= $topbar_action ?? '' ?>
</header>