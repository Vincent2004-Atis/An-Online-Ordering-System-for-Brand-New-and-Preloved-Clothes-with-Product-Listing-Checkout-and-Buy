<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once '../config/database.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db     = getDB();
$userId = (int)$_SESSION['user_id'];

// ── POST: mark as read ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';

    if ($action === 'mark_read') {
        $nid  = (int)($input['notification_id'] ?? 0);
        $stmt = $db->prepare("UPDATE notifications SET is_read=1 WHERE notification_id=? AND user_id=?");
        $stmt->bind_param('ii', $nid, $userId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'mark_all_read') {
        $stmt = $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

// ── GET: fetch notifications ──────────────────────────────────────
$stmt = $db->prepare("
    SELECT n.notification_id, n.order_id, n.title, n.message, n.is_read, n.created_at,
           o.order_status, o.total_amount
    FROM notifications n
    JOIN orders o ON o.order_id = n.order_id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 30
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count unread (simple loop — no bugs)
$unread = 0;
foreach ($notifications as $n) {
    if ((int)$n['is_read'] === 0) $unread++;
}

echo json_encode([
    'success'       => true,
    'unread_count'  => $unread,
    'notifications' => $notifications
]);
