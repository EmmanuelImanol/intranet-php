-- ──────────────────────────────────────────
-- Permissions table + seed
-- Run once: mysql -u intranet_user -p intranet < permissions_migration.sql
-- ──────────────────────────────────────────

CREATE TABLE IF NOT EXISTS permissions (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  module     ENUM('noticias','documentos','usuarios') NOT NULL,
  role       ENUM('supervisor','empleado') NOT NULL,
  can_view   TINYINT(1) NOT NULL DEFAULT 1,
  can_create TINYINT(1) NOT NULL DEFAULT 0,
  can_edit   TINYINT(1) NOT NULL DEFAULT 0,
  can_delete TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_module_role (module, role)
);

-- Default permissions (mirrors current hardcoded behaviour)
INSERT INTO permissions (module, role, can_view, can_create, can_edit, can_delete)
VALUES
  -- Noticias
  ('noticias',   'supervisor', 1, 1, 1, 1),
  ('noticias',   'empleado',   1, 0, 0, 0),
  -- Documentos
  ('documentos', 'supervisor', 1, 1, 1, 1),
  ('documentos', 'empleado',   1, 0, 0, 0),
  -- Usuarios
  ('usuarios',   'supervisor', 1, 1, 1, 0),
  ('usuarios',   'empleado',   0, 0, 0, 0)
ON DUPLICATE KEY UPDATE
  can_view   = VALUES(can_view),
  can_create = VALUES(can_create),
  can_edit   = VALUES(can_edit),
  can_delete = VALUES(can_delete);
