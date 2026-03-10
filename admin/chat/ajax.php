<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// KIRIM PESAN
if ($action === 'send') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    if (!$user_id || !$message) { echo json_encode(['ok'=>false]); exit; }
    $stmt = $conn->prepare("INSERT INTO messages (user_id, sender, message) VALUES (?, 'admin', ?)");
    $stmt->bind_param('is', $user_id, $message);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['ok'=>true]);
    exit;
}

// AMBIL PESAN
if ($action === 'fetch') {
    $user_id  = (int)($_GET['user_id'] ?? 0);
    $after_id = (int)($_GET['after_id'] ?? 0);
    if (!$user_id) { echo json_encode([]); exit; }
    $conn->query("UPDATE messages SET is_read=1 WHERE user_id=$user_id AND sender='user' AND is_read=0");
    $rows = $conn->query("SELECT id, sender, message, created_at FROM messages WHERE user_id=$user_id AND id > $after_id ORDER BY id ASC");
    $msgs = [];
    while ($r = $rows->fetch_assoc()) $msgs[] = $r;
    echo json_encode($msgs);
    exit;
}

// DAFTAR USER YANG PERNAH CHAT
if ($action === 'user_list') {
    $rows = $conn->query("
        SELECT u.id, u.name, u.email,
               MAX(m.created_at) AS last_msg,
               SUM(m.sender='user' AND m.is_read=0) AS unread,
               (SELECT message FROM messages WHERE user_id=u.id ORDER BY id DESC LIMIT 1) AS last_text
        FROM messages m
        JOIN users u ON m.user_id = u.id
        GROUP BY u.id
        ORDER BY last_msg DESC
    ");
    $list = [];
    while ($r = $rows->fetch_assoc()) $list[] = $r;
    echo json_encode($list);
    exit;
}

echo json_encode(['ok'=>false,'msg'=>'unknown action']);