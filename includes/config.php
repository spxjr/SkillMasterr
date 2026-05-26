<?php
// ============================================================
//  Texas Skill Masters CRM — Database Configuration
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'oph0n93djre1wlxy_texass');
define('DB_USER', 'oph0n93djre1wlxy_skillmaster');
define('DB_PASS', 'iwqs!2=Rj;;sgjrQ');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Texas Skill Masters CRM');
define('APP_VERSION', '1.0');
define('TIMEZONE', 'America/Chicago');

// Base URL — all links and asset paths resolve from here
define('BASE_URL', '/CRM');

date_default_timezone_set(TIMEZONE);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function formatMoney(float $amount): string {
    return '$' . number_format($amount, 2);
}

function formatDate(string $date): string {
    if (empty($date) || $date === '0000-00-00') return '—';
    return date('M j, Y', strtotime($date));
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function flashMessage(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ── Admin Auth ───────────────────────────────────────────────

function adminRequireLogin(): void {
    if (empty($_SESSION['admin_id'])) {
        redirect(BASE_URL . '/login.php');
    }
}

function adminLogin(string $username, string $password): bool {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE username=? AND is_active=1");
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) return false;
    $_SESSION['admin_id']       = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_name']     = $user['full_name'];
    $_SESSION['admin_role']     = $user['role'];
    $db->prepare("UPDATE admin_users SET last_login=NOW() WHERE id=?")->execute([$user['id']]);
    return true;
}

function adminLogout(): void {
    unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_name'], $_SESSION['admin_role']);
    session_destroy();
    redirect(BASE_URL . '/login.php');
}

function adminGetUser(): array {
    return [
        'id'       => $_SESSION['admin_id']       ?? 0,
        'username' => $_SESSION['admin_username']  ?? '',
        'name'     => $_SESSION['admin_name']      ?? 'Admin',
        'role'     => $_SESSION['admin_role']      ?? 'admin',
    ];
}

function adminUpdatePassword(int $id, string $newPassword): void {
    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
    getDB()->prepare("UPDATE admin_users SET password_hash=? WHERE id=?")->execute([$hash, $id]);
}

// ── Sales Rep Auth ───────────────────────────────────────────

define('SALES_URL', BASE_URL . '/sales');

function salesRequireLogin(): void {
    if (empty($_SESSION['rep_id'])) {
        redirect(SALES_URL . '/login.php');
    }
}

function salesLogin(string $username, string $password): bool {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM sales_reps WHERE username=? AND is_active=1");
    $stmt->execute([trim($username)]);
    $rep  = $stmt->fetch();
    if (!$rep || !password_verify($password, $rep['password_hash'])) return false;
    $_SESSION['rep_id']        = $rep['id'];
    $_SESSION['rep_username']  = $rep['username'];
    $_SESSION['rep_name']      = $rep['full_name'];
    $_SESSION['rep_firstname'] = $rep['first_name'];
    $_SESSION['rep_territory'] = $rep['territory'];
    $db->prepare("UPDATE sales_reps SET last_login=NOW() WHERE id=?")->execute([$rep['id']]);
    return true;
}

function salesLogout(): void {
    unset($_SESSION['rep_id'], $_SESSION['rep_username'], $_SESSION['rep_name'],
          $_SESSION['rep_firstname'], $_SESSION['rep_territory']);
    session_destroy();
    redirect(SALES_URL . '/login.php');
}

function salesGetRep(): array {
    return [
        'id'        => $_SESSION['rep_id']        ?? 0,
        'username'  => $_SESSION['rep_username']   ?? '',
        'name'      => $_SESSION['rep_name']       ?? 'Rep',
        'firstname' => $_SESSION['rep_firstname']  ?? 'Rep',
        'territory' => $_SESSION['rep_territory']  ?? '',
    ];
}

function salesUpdatePassword(int $id, string $newPassword): void {
    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
    getDB()->prepare("UPDATE sales_reps SET password_hash=? WHERE id=?")->execute([$hash, $id]);
}
