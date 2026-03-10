<?php
/**
 * Reusable closing scripts component.
 *
 * Optional vars:
 *   $extra_js  — array of JS paths relative to root e.g. ['../../public/js/news.js']
 *   $inline_js — string of raw inline JS to inject (without <script> tags)
 */
$extra_js  = $extra_js  ?? [];
$inline_js = $inline_js ?? '';
?>
  <script src="../../public/js/dashboard.js"></script>
  <?php foreach ($extra_js as $js): ?>
  <script src="<?= htmlspecialchars($js) ?>"></script>
  <?php endforeach; ?>
  <?php if ($inline_js): ?>
  <script><?= $inline_js ?></script>
  <?php endif; ?>
</body>
</html>