<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireUser();

header('Content-Type: application/json');

$action   = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id  = $_SESSION['user_id'];

// KIRIM PESAN
if ($action === 'send') {
    $message = trim($_POST['message'] ?? '');
    if (!$message) { echo json_encode(['ok'=>false]); exit; }

    $stmt = $conn->prepare("INSERT INTO messages (user_id, sender, message) VALUES (?, 'user', ?)");
    $stmt->bind_param('is', $user_id, $message);
    $stmt->execute();
    $stmt->close();

    // Buat notifikasi untuk admin
    $preview = mb_substr($message, 0, 80);
    $notif_msg = "Pesan baru dari " . $_SESSION['user_name'] . ": " . $preview;
    $stmt2 = $conn->prepare("INSERT INTO notifications (type, reference_id, message) VALUES ('message', ?, ?)");
    $stmt2->bind_param('is', $user_id, $notif_msg);
    $stmt2->execute();
    $stmt2->close();

    echo json_encode(['ok'=>true]);
    exit;
}

// AMBIL PESAN
if ($action === 'fetch') {
    $after_id = (int)($_GET['after_id'] ?? 0);
    // Tandai pesan admin sbg dibaca
    $conn->query("UPDATE messages SET is_read=1 WHERE user_id=$user_id AND sender='admin' AND is_read=0");

    $rows = $conn->query("SELECT id, sender, message, created_at FROM messages WHERE user_id=$user_id AND id > $after_id ORDER BY id ASC");
    $msgs = [];
    while ($r = $rows->fetch_assoc()) $msgs[] = $r;
    echo json_encode($msgs);
    exit;
}

echo json_encode(['ok'=>false]);