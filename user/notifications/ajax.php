<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireUser();

header('Content-Type: application/json');

$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($action === 'fetch') {
    $list = [];

    // Notif update status pesanan (7 hari terakhir, non-pending)
    $q = $conn->query("
        SELECT id,
               CONCAT('Pesanan #', id, ' - ',
                   CASE status
                       WHEN 'processing' THEN 'Sedang diproses oleh admin'
                       WHEN 'shipped'    THEN 'Pesanan sedang dikirim'
                       WHEN 'delivered'  THEN 'Pesanan telah diterima'
                       WHEN 'cancelled'  THEN 'Pesanan dibatalkan'
                       ELSE status
                   END
               ) AS message,
               'order' AS type,
               updated_at AS created_at
        FROM orders
        WHERE user_id = $user_id
          AND status != 'pending'
          AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY updated_at DESC LIMIT 10
    ");
    while ($r = $q->fetch_assoc()) $list[] = $r;

    // Notif pesan baru dari admin (belum dibaca)
    $q2 = $conn->query("
        SELECT id,
               CONCAT('Admin membalas: ', LEFT(message,60), IF(LENGTH(message)>60,'...','')) AS message,
               'message' AS type,
               created_at
        FROM messages
        WHERE user_id=$user_id AND sender='admin' AND is_read=0
        ORDER BY created_at DESC LIMIT 10
    ");
    while ($r = $q2->fetch_assoc()) $list[] = $r;

    usort($list, fn($a,$b) => strtotime($b['created_at']) - strtotime($a['created_at']));
    $list = array_slice($list, 0, 15);

    $unread_chat = (int)$conn->query("SELECT COUNT(*) AS c FROM messages WHERE user_id=$user_id AND sender='admin' AND is_read=0")->fetch_assoc()['c'];
    $total = $unread_chat; // badge hanya untuk chat belum dibaca

    echo json_encode(['list' => $list, 'total' => $total]);
    exit;
}

if ($action === 'mark_read') {
    $conn->query("UPDATE messages SET is_read=1 WHERE user_id=$user_id AND sender='admin'");
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false]);