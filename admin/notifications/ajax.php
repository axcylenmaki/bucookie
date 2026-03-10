<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'fetch') {
    $rows = $conn->query("SELECT * FROM notifications WHERE is_read=0 ORDER BY created_at DESC LIMIT 20");
    $list = [];
    while ($r = $rows->fetch_assoc()) $list[] = $r;
    $total = (int)$conn->query("SELECT COUNT(*) AS c FROM notifications WHERE is_read=0")->fetch_assoc()['c'];
    echo json_encode(['list'=>$list,'total'=>$total]);
    exit;
}

if ($action === 'mark_read') {
    $conn->query("UPDATE notifications SET is_read=1");
    echo json_encode(['ok'=>true]);
    exit;
}

if ($action === 'mark_one') {
    $id = (int)($_POST['id'] ?? 0);
    $conn->query("UPDATE notifications SET is_read=1 WHERE id=$id");
    echo json_encode(['ok'=>true]);
    exit;
}

echo json_encode(['ok'=>false]);