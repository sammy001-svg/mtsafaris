<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// =============================================================
// Authentication
// =============================================================

function currentUser(): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    static $user = null;
    if ($user === null) {
        $user = DB::row("SELECT id, uuid, first_name, last_name, email, phone, role, status, avatar
                         FROM users WHERE id = ? AND status = 'active'", [$_SESSION['user_id']]);
        if (!$user) {
            unset($_SESSION['user_id']);
            return null;
        }
    }
    return $user;
}

function isLoggedIn(): bool {
    return currentUser() !== null;
}

function isAdmin(): bool {
    $user = currentUser();
    return $user && in_array($user['role'], ADMIN_ROLES);
}

function isRole(string|array $roles): bool {
    $user = currentUser();
    if (!$user) return false;
    $roles = (array) $roles;
    return in_array($user['role'], $roles);
}

function requireLogin(string $redirect = '/portal/login.php'): void {
    if (!isLoggedIn()) {
        $_SESSION['login_redirect'] = currentUrl();
        redirect(APP_URL . $redirect);
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        if (!isLoggedIn()) {
            $_SESSION['login_redirect'] = currentUrl();
            redirect(APP_URL . '/admin/login.php');
        }
        redirect(APP_URL . '/admin/');
    }
}

function requireRole(string|array $roles): void {
    if (!isRole($roles)) {
        flash('error', 'You do not have permission to access this area.');
        redirect(APP_URL . '/admin/');
    }
}

// =============================================================
// Login / Logout
// =============================================================

function login(string $email, string $password, bool $remember = false): array {
    $user = DB::row("SELECT * FROM users WHERE email = ?", [trim($email)]);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }
    if ($user['status'] !== 'active') {
        return ['success' => false, 'message' => 'Your account is not active. Please contact support.'];
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];

    DB::update('users', ['last_login_at' => date('Y-m-d H:i:s'), 'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? ''], ['id' => $user['id']]);

    if ($remember) {
        $token   = generateToken();
        $expires = date('Y-m-d H:i:s', strtotime('+' . REMEMBER_DAYS . ' days'));
        DB::insert('user_sessions', [
            'user_id'    => $user['id'],
            'token'      => hash('sha256', $token),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'expires_at' => $expires,
        ]);
        setcookie('remember_token', $token, strtotime('+' . REMEMBER_DAYS . ' days'), '/', '', false, true);
    }

    auditLog('login', 'users', $user['id']);
    return ['success' => true, 'user' => $user];
}

function logout(): void {
    if (isset($_COOKIE['remember_token'])) {
        DB::query("DELETE FROM user_sessions WHERE token = ?", [hash('sha256', $_COOKIE['remember_token'])]);
        setcookie('remember_token', '', time() - 3600, '/');
    }
    if (isset($_SESSION['user_id'])) {
        auditLog('logout', 'users', $_SESSION['user_id']);
    }
    session_destroy();
    redirect(APP_URL . '/portal/login.php');
}

// =============================================================
// Registration
// =============================================================

function register(array $data): array {
    $email = trim(strtolower($data['email']));

    if (DB::value("SELECT id FROM users WHERE email = ?", [$email])) {
        return ['success' => false, 'message' => 'An account with this email already exists.'];
    }

    $token = generateToken();
    $id    = DB::insert('users', [
        'first_name'         => trim($data['first_name']),
        'last_name'          => trim($data['last_name']),
        'email'              => $email,
        'phone'              => trim($data['phone'] ?? ''),
        'password_hash'      => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
        'role'               => 'customer',
        'status'             => 'active',
        'email_verify_token' => $token,
        'email_verified'     => 0,
    ]);

    return ['success' => true, 'user_id' => $id, 'token' => $token];
}

// =============================================================
// Remember-me auto login
// =============================================================

function checkRememberToken(): void {
    if (isLoggedIn() || !isset($_COOKIE['remember_token'])) return;

    $hash    = hash('sha256', $_COOKIE['remember_token']);
    $session = DB::row("SELECT * FROM user_sessions
                        WHERE token = ? AND expires_at > NOW()", [$hash]);
    if (!$session) {
        setcookie('remember_token', '', time() - 3600, '/');
        return;
    }
    $user = DB::row("SELECT * FROM users WHERE id = ? AND status = 'active'", [$session['user_id']]);
    if ($user) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
    }
}

// =============================================================
// Audit log
// =============================================================

function auditLog(string $action, string $model = '', int $modelId = 0, array $old = [], array $new = []): void {
    try {
        DB::insert('audit_logs', [
            'user_id'    => $_SESSION['user_id'] ?? null,
            'action'     => $action,
            'model'      => $model,
            'model_id'   => $modelId ?: null,
            'old_values' => $old ? json_encode($old) : null,
            'new_values' => $new ? json_encode($new) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);
    } catch (Exception) {}
}

checkRememberToken();
