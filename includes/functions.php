<?php
/**
 * FoodBridge — functions.php
 * Small shared helpers: escaping, redirects, flash messages, URLs.
 */

/** Escape output to prevent XSS. Use everywhere you print user data. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Build a URL relative to the project base. */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Redirect to a path within the app and stop execution. */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** Store a one-time flash message (type: success | error | info). */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Read and clear the flash message. Returns null if none. */
function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/** Render the flash message as a styled alert (call once near top of body). */
function render_flash(): string
{
    $flash = get_flash();
    if (!$flash) {
        return '';
    }
    $colors = [
        'success' => 'background:var(--fb-primary-light);color:var(--fb-primary-hover);',
        'error'   => 'background:#FEE2E2;color:#B91C1C;',
        'info'    => 'background:var(--fb-blue-soft);color:#1D4ED8;',
    ];
    $style = $colors[$flash['type']] ?? $colors['info'];
    return '<div style="padding:12px 16px;border-radius:12px;margin-bottom:16px;font-weight:600;' . $style . '">'
        . e($flash['message']) . '</div>';
}

/** Trim + strip tags from a posted field. */
function clean(?string $value): string
{
    return trim(strip_tags((string) $value));
}

/** Human-friendly "time ago" string. */
function time_ago($datetime): string
{
    $ts = is_numeric($datetime) ? (int) $datetime : strtotime((string) $datetime);
    $diff = time() - $ts;
    if ($diff < 60)       return $diff . ' secs ago';
    if ($diff < 3600)     return floor($diff / 60) . ' mins ago';
    if ($diff < 86400)    return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800)   return floor($diff / 86400) . ' days ago';
    return date('d M Y', $ts);
}

