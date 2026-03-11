<?php
/**
 * Reusable <head> component.
 *
 * Required vars:
 *   $page_title  — string  e.g. 'Dashboard'
 *
 * Optional vars:
 *   $extra_css   — array of CSS paths relative to root e.g. ['../../public/css/news.css']
 */
$extra_css = $extra_css ?? [];
?>
<head>
  <script>var t=localStorage.getItem("theme")||"light";if(t==="light")document.documentElement.classList.add("light");</script>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Intranet — <?= htmlspecialchars($page_title ?? 'Panel') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../../public/css/dashboard.css" />
  <?php foreach ($extra_css as $css): ?>
  <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>" />
  <?php endforeach; ?>
</head>
