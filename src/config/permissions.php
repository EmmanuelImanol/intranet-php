<?php
/**
 * Permissions helper.
 *
 * Usage (after session_start + connectDB):
 *   require_once __DIR__ . '/../config/permissions.php';
 *   loadPermissions($db);          // call once per page
 *
 *   can('noticias',   'create')    // bool
 *   can('documentos', 'delete')    // bool
 *   can('usuarios',   'view')      // bool
 *
 * Admin always returns true for everything.
 * Permissions are cached in $_SESSION['_perms'] for the request.
 */

function loadPermissions(PDO $db): void
{
    // Admin is omnipotent — no DB lookup needed
    if (($_SESSION['user_role'] ?? '') === 'admin') {
        return;
    }

    $role = $_SESSION['user_role'] ?? 'empleado';

    // Cache in session-request key to avoid repeated queries
    if (isset($_SESSION['_perms_role']) && $_SESSION['_perms_role'] === $role && isset($_SESSION['_perms'])) {
        return;
    }

    $stmt = $db->prepare("SELECT module, can_view, can_create, can_edit, can_delete FROM permissions WHERE role = ?");
    $stmt->execute([$role]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $perms = [];
    foreach ($rows as $row) {
        $perms[$row['module']] = [
            'view'   => (bool)$row['can_view'],
            'create' => (bool)$row['can_create'],
            'edit'   => (bool)$row['can_edit'],
            'delete' => (bool)$row['can_delete'],
        ];
    }

    $_SESSION['_perms']      = $perms;
    $_SESSION['_perms_role'] = $role;
}

function can(string $module, string $action): bool
{
    // Admin can always do everything
    if (($_SESSION['user_role'] ?? '') === 'admin') {
        return true;
    }

    return (bool)($_SESSION['_perms'][$module][$action] ?? false);
}

/**
 * Redirect with 403 if permission denied.
 * Usage: requirePerm('usuarios', 'view', 'dashboard.php');
 */
function requirePerm(string $module, string $action, string $redirect = 'dashboard.php'): void
{
    if (!can($module, $action)) {
        header("Location: $redirect");
        exit;
    }
}

/**
 * Invalidate cached permissions (call after admin updates them).
 */
function clearPermissionsCache(): void
{
    unset($_SESSION['_perms'], $_SESSION['_perms_role']);
}
