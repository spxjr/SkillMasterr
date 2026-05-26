<?php
// ============================================================
//  Texas Skill Masters — Client Portal Config & Auth
// ============================================================

require_once __DIR__ . '/../../includes/config.php';

define('PORTAL_URL', BASE_URL . '/portal');

// ── Auth helpers ─────────────────────────────────────────────

function portalRequireLogin(): void {
    if (!isset($_SESSION['portal_user_id'])) {
        redirect(PORTAL_URL . '/login.php');
    }
}

function portalGetUser(): ?array {
    if (!isset($_SESSION['portal_user_id'])) return null;
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT cu.*, c.business_name, c.status AS client_status,
               c.portal_enabled, c.city, c.state, c.venue_type,
               c.phone AS biz_phone, c.email AS biz_email,
               c.address, c.zip, c.contract_start, c.contract_end
        FROM client_users cu
        JOIN clients c ON c.id = cu.client_id
        WHERE cu.id = ? AND cu.is_active = 1
    ");
    $stmt->execute([$_SESSION['portal_user_id']]);
    $user = $stmt->fetch();
    if (!$user || !$user['portal_enabled']) {
        portalLogout();
        return null;
    }
    return $user;
}

function portalLogin(string $username, string $password): bool {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT cu.*, c.portal_enabled
        FROM client_users cu
        JOIN clients c ON c.id = cu.client_id
        WHERE cu.username = ? AND cu.is_active = 1
    ");
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch();
    if (!$user || !$user['portal_enabled']) return false;
    if (!password_verify($password, $user['password_hash'])) return false;

    $_SESSION['portal_user_id']  = $user['id'];
    $_SESSION['portal_client_id']= $user['client_id'];

    // Update last login
    $db->prepare("UPDATE client_users SET last_login=NOW() WHERE id=?")->execute([$user['id']]);
    return true;
}

function portalLogout(): void {
    unset($_SESSION['portal_user_id'], $_SESSION['portal_client_id']);
    session_destroy();
    redirect(PORTAL_URL . '/login.php');
}

// ── Admin: create/manage portal users ───────────────────────

function createPortalUser(int $clientId, string $username, string $password, string $fullName, string $email, string $role = 'owner'): bool {
    $db   = getDB();
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    try {
        $db->prepare("INSERT INTO client_users (client_id,username,password_hash,full_name,email,role) VALUES (?,?,?,?,?,?)")
           ->execute([$clientId, trim($username), $hash, $fullName, $email, $role]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function updatePortalPassword(int $userId, string $newPassword): void {
    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    getDB()->prepare("UPDATE client_users SET password_hash=? WHERE id=?")->execute([$hash, $userId]);
}
