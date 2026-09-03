<?php
/**
 * FoodBridge — queries.php
 * All dashboard data-fetching helpers in one place.
 * Every query uses prepared statements (safe from SQL injection).
 */

/* ------------------------------------------------------------------ *
 *  USER (giver / receiver / volunteer) statistics
 * ------------------------------------------------------------------ */

/** Count of donations posted by a giver. */
function count_user_donations(int $userId): int
{
    global $conn;
    $stmt = $conn->prepare('SELECT COUNT(*) c FROM food_posts WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_assoc()['c'];
}

/** Count of requests made by a receiver. */
function count_user_requests(int $userId): int
{
    global $conn;
    $stmt = $conn->prepare('SELECT COUNT(*) c FROM requests WHERE receiver_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_assoc()['c'];
}

/** Count of this user's requests that are approved/assigned/delivered. */
function count_user_approved(int $userId): int
{
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM requests WHERE receiver_id = ? AND status IN ('approved','assigned','delivered')");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_assoc()['c'];
}

/** How many people this giver helped (sum: each completed donation counts). */
function count_people_helped(int $userId): int
{
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM food_posts WHERE user_id = ? AND status = 'completed'");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_assoc()['c'];
}

/** Recent donations for a giver (or all donations if $userId is null, for admin). */
function recent_donations(?int $userId = null, int $limit = 5): array
{
    global $conn;
    if ($userId === null) {
        $stmt = $conn->prepare(
            'SELECT fp.*, u.name AS donor FROM food_posts fp
             JOIN users u ON u.id = fp.user_id
             ORDER BY fp.created_at DESC LIMIT ?'
        );
        $stmt->bind_param('i', $limit);
    } else {
        $stmt = $conn->prepare(
            'SELECT fp.*, u.name AS donor FROM food_posts fp
             JOIN users u ON u.id = fp.user_id
             WHERE fp.user_id = ? ORDER BY fp.created_at DESC LIMIT ?'
        );
        $stmt->bind_param('ii', $userId, $limit);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/** Recent requests for a receiver (or all if $userId is null, for admin). */
function recent_requests(?int $userId = null, int $limit = 5): array
{
    global $conn;
    if ($userId === null) {
        $stmt = $conn->prepare(
            'SELECT r.*, u.name AS requester FROM requests r
             JOIN users u ON u.id = r.receiver_id
             ORDER BY r.created_at DESC LIMIT ?'
        );
        $stmt->bind_param('i', $limit);
    } else {
        $stmt = $conn->prepare(
            'SELECT r.*, u.name AS requester FROM requests r
             JOIN users u ON u.id = r.receiver_id
             WHERE r.receiver_id = ? ORDER BY r.created_at DESC LIMIT ?'
        );
        $stmt->bind_param('ii', $userId, $limit);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ------------------------------------------------------------------ *
 *  ADMIN / platform-wide statistics
 * ------------------------------------------------------------------ */

/** Single scalar count helper. */
function scalar_count(string $sql): int
{
    global $conn;
    $res = $conn->query($sql);
    return (int) ($res->fetch_assoc()['c'] ?? 0);
}

/** Platform totals for the admin dashboard top cards. */
function admin_totals(): array
{
    return [
        'donations'    => scalar_count('SELECT COUNT(*) c FROM food_posts'),
        'requests'     => scalar_count('SELECT COUNT(*) c FROM requests'),
        'people_helped'=> scalar_count("SELECT COUNT(*) c FROM requests WHERE status = 'delivered'"),
        'active_donors'=> scalar_count("SELECT COUNT(DISTINCT user_id) c FROM food_posts"),
        'volunteers'   => scalar_count("SELECT COUNT(*) c FROM users WHERE role = 'volunteer'"),
    ];
}

/** Donation category breakdown (for the donut chart). Returns label => count. */
function donation_categories(): array
{
    global $conn;
    $rows = $conn->query(
        "SELECT food_type, COUNT(*) c FROM food_posts GROUP BY food_type"
    )->fetch_all(MYSQLI_ASSOC);

    $labels = ['cooked' => 'Cooked Food', 'groceries' => 'Groceries', 'vegetables' => 'Vegetables', 'fruits' => 'Fruits', 'other' => 'Others'];
    $out = [];
    foreach ($labels as $key => $label) {
        $out[$label] = 0;
    }
    foreach ($rows as $r) {
        $label = $labels[$r['food_type']] ?? 'Others';
        $out[$label] += (int) $r['c'];
    }
    return $out;
}

/** Donations per day for the last 14 days (for the line chart). */
function donations_timeline(int $days = 14): array
{
    global $conn;
    $rows = $conn->query(
        "SELECT DATE(created_at) d, COUNT(*) c
         FROM food_posts
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL " . (int)$days . " DAY)
         GROUP BY DATE(created_at)"
    )->fetch_all(MYSQLI_ASSOC);

    $map = [];
    foreach ($rows as $r) { $map[$r['d']] = (int) $r['c']; }

    $labels = [];
    $data   = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i day"));
        $labels[] = date('d M', strtotime($day));
        $data[]   = $map[$day] ?? 0;
    }
    return ['labels' => $labels, 'data' => $data];
}

/* ------------------------------------------------------------------ *
 *  NOTIFICATIONS
 * ------------------------------------------------------------------ */

/** Count of unread notifications for a user. */
function unread_notifications(int $userId): int
{
    global $conn;
    $stmt = $conn->prepare('SELECT COUNT(*) c FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_assoc()['c'];
}

/* ------------------------------------------------------------------ *
 *  Small presentation helpers
 * ------------------------------------------------------------------ */

/** Icon (Lucide name) for a food type. */
function food_type_icon(string $type): string
{
    return [
        'cooked'     => 'utensils',
        'groceries'  => 'shopping-basket',
        'vegetables' => 'carrot',
        'fruits'     => 'apple',
        'other'      => 'package',
    ][$type] ?? 'package';
}

/** Badge CSS class for a status. */
function status_badge_class(string $status): string
{
    return [
        'available' => 'fb-badge-success',
        'completed' => 'fb-badge-success',
        'delivered' => 'fb-badge-success',
        'pending'   => 'fb-badge-pending',
        'approved'  => 'fb-badge-approved',
        'assigned'  => 'fb-badge-approved',
        'claimed'   => 'fb-badge-approved',
        'expired'   => 'fb-badge-danger',
        'rejected'  => 'fb-badge-danger',
    ][$status] ?? 'fb-badge-pending';
}