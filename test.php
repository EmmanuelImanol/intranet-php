<?php
require_once __DIR__ . '/src/config/database.php';

$db = connectDB();

if ($db) {
  echo "✅ Conexión exitosa a la base de datos\n";
}