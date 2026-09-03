<?php
/**
 * FoodBridge — auth.php
 * Authentication + role-based access control.
 *
 * Roles: admin | giver | receiver | volunteer
 */

/** Is someone logged in right now? */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/** Return the current logged-in user row (array) or null. Cached per request. */
function current_user(): ?array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached ?: null;
    }
    if (!is_logged_in()) {
        $cached = false;
        return null;
    }
    global $conn;
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $cached = $user ?: false;
    return $user ?: null;
}

/** Current user's role, or null. */
function current_role(): ?string
{
    $u = current_user();
    return $u['role'] ?? null;
}

/** Log a user in: verify credentials, set session. Returns user array or null. */
function attempt_login(string $email, string $password): ?array
{
    global $conn;
    $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return null;
    }
    if ($user['status'] === 'blocked') {
        return null;
    }
    if (!password_verify($password, $user['password_hash'])) {
        return null;
    }

    // Success — establish session
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['name']    = $user['name'];
    return $user;
}

/** Register a new user. Returns new user id, or 0 if email already exists. */
function register_user(string $name, string $email, string $phone, string $password, string $role, string $location): int
{
    global $conn;

    // Reject duplicate email
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        return 0;
    }
    $stmt->close();

    $allowed = ['giver', 'receiver', 'volunteer'];
    if (!in_array($role, $allowed, true)) {
        $role = 'giver';
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        'INSERT INTO users (name, email, phone, password_hash, role, location) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('ssssss', $name, $email, $phone, $hash, $role, $location);
    $stmt->execute();
    $newId = (int) $stmt->insert_id;
    $stmt->close();

    // If volunteer, create the volunteer profile row
    if ($role === 'volunteer' && $newId > 0) {
        $stmt = $conn->prepare('INSERT INTO volunteers (user_id) VALUES (?)');
        $stmt->bind_param('i', $newId);
        $stmt->execute();
        $stmt->close();
    }

    return $newId;
}

/** Destroy the session and log out. */
function logout_user(): void
{
    $_SESSION = [];
    session_destroy();
}

/** Require a logged-in user; otherwise redirect to login. */
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to continue.');
        redirect('login.php');
    }
}

/**
 * Require one of the given roles; otherwise redirect to the user's own dashboard.
 * Usage: require_role('admin');  or  require_role(['giver','receiver']);
 */
function require_role($roles): void
{
    require_login();
    $roles = (array) $roles;
    if (!in_array(current_role(), $roles, true)) {
        set_flash('error', 'You do not have access to that page.');
        redirect(dashboard_for(current_role()));
    }
}

/** Map a role to its home dashboard path. */
function dashboard_for(?string $role): string
{
    return $role === 'admin' ? 'admin/dashboard.php' : 'dashboard.php';
}