<?php
session_start();
require_once __DIR__ . '/src/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Por favor completa todos los campos.';
    } else {
        $db = connectDB();

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_area'] = $user['area'] ?? '';
            header('Location: src/pages/dashboard.php');
            exit;
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Intranet — Iniciar sesión</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="public/css/login.css" />
</head>
<body>

  <!-- Panel izquierdo -->
  <div class="panel-left">
    <div class="grid-lines"></div>

    <div class="logo-badge">
      <div class="logo-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2">
          <rect x="3" y="3" width="7" height="7" rx="1"/>
          <rect x="14" y="3" width="7" height="7" rx="1"/>
          <rect x="3" y="14" width="7" height="7" rx="1"/>
          <rect x="14" y="14" width="7" height="7" rx="1"/>
        </svg>
      </div>
      <span class="logo-label">Intranet</span>
    </div>

    <div class="hero-text">
      <h1>Tu espacio<br/>de trabajo<br/><span>centralizado</span></h1>
      <p>Accede a documentos, noticias y herramientas de tu organización en un solo lugar.</p>
    </div>

    <div class="feature-list">
      <div class="feature-item"><div class="feature-dot"></div> Gestión de documentos por área</div>
      <div class="feature-item"><div class="feature-dot"></div> Panel de noticias y anuncios</div>
      <div class="feature-item"><div class="feature-dot"></div> Áreas de trabajo por rol</div>
      <div class="feature-item"><div class="feature-dot"></div> Acceso seguro con autenticación</div>
    </div>
  </div>

  <!-- Panel derecho -->
  <div class="panel-right">
    <div class="form-card">

      <h2>Bienvenido</h2>
      <p>Ingresa tus credenciales para continuar</p>

      <?php if (!empty($error)): ?>
      <div class="error-box">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" id="loginForm">

        <div class="field-group">
          <label for="email">Correo electrónico</label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="usuario@empresa.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required
            autocomplete="email"
          />
        </div>

        <div class="field-group">
          <label for="password">Contraseña</label>
          <div class="pass-wrapper">
            <input
              type="password"
              id="password"
              name="password"
              placeholder="••••••••"
              required
              autocomplete="current-password"
            />
            <button type="button" class="toggle-pass" id="togglePass" aria-label="Mostrar contraseña">
              <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-login" id="btnLogin">
          Iniciar sesión
        </button>

      </form>
    </div>
  </div>

  <script src="public/js/login.js"></script>

</body>
</html>
