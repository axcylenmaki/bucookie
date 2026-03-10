<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireUser();

$order_id = (int)($_POST['order_id'] ?? 0);

if (!$order_id) {
    header('Location: ' . BASE_URL . 'user/orders/index.php');
    exit;
}

// Pastikan pesanan milik user ini dan statusnya pending
$stmt = $conn->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order || $order['status'] !== 'pending') {
    header('Location: ' . BASE_URL . 'user/orders/index.php?msg=cancel_failed');
    exit;
}

// Kembalikan stok
$items = $conn->query("SELECT book_id, quantity FROM order_items WHERE order_id = $order_id");
while ($item = $items->fetch_assoc()) {
    $stmt = $conn->prepare("UPDATE books SET stock = stock + ? WHERE id = ?");
    $stmt->bind_param('ii', $item['quantity'], $item['book_id']);
    $stmt->execute();
    $stmt->close();
}

// Update status ke cancelled
$stmt = $conn->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$stmt->close();

header('Location: ' . BASE_URL . 'user/orders/index.php?msg=cancelled&order_id=' . $order_id);
exit;