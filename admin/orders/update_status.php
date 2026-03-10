<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

$order_id   = (int)($_POST['order_id'] ?? 0);
$new_status = $_POST['status'] ?? '';
$allowed    = ['pending','processing','shipped','delivered','cancelled'];

if (!$order_id || !in_array($new_status, $allowed)) {
    header('Location: ' . BASE_URL . 'admin/orders/index.php');
    exit;
}

// Ambil status lama
$stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: ' . BASE_URL . 'admin/orders/index.php');
    exit;
}

$old_status = $order['status'];

// Tidak bisa mengubah pesanan yang sudah cancelled
if ($old_status === 'cancelled') {
    header('Location: ' . BASE_URL . 'admin/orders/index.php?msg=already_cancelled');
    exit;
}

// Kalau status berubah ke cancelled → kembalikan stok
if ($new_status === 'cancelled' && $old_status !== 'cancelled') {
    $items = $conn->query("SELECT book_id, quantity FROM order_items WHERE order_id = $order_id");
    while ($item = $items->fetch_assoc()) {
        $stmt = $conn->prepare("UPDATE books SET stock = stock + ? WHERE id = ?");
        $stmt->bind_param('ii', $item['quantity'], $item['book_id']);
        $stmt->execute();
        $stmt->close();
    }
}

// Kalau dari cancelled balik ke status lain → kurangi stok lagi
if ($old_status === 'cancelled' && $new_status !== 'cancelled') {
    $items = $conn->query("SELECT book_id, quantity FROM order_items WHERE order_id = $order_id");
    while ($item = $items->fetch_assoc()) {
        // Cek stok cukup dulu
        $s = $conn->prepare("SELECT stock FROM books WHERE id = ?");
        $s->bind_param('i', $item['book_id']);
        $s->execute();
        $current_stock = (int)$s->get_result()->fetch_assoc()['stock'];
        $s->close();

        $deduct = min($item['quantity'], $current_stock); // jangan sampai minus
        $stmt   = $conn->prepare("UPDATE books SET stock = stock - ? WHERE id = ?");
        $stmt->bind_param('ii', $deduct, $item['book_id']);
        $stmt->execute();
        $stmt->close();
    }
}

// Update status pesanan
$stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param('si', $new_status, $order_id);
$stmt->execute();
$stmt->close();

header('Location: ' . BASE_URL . 'admin/orders/index.php?msg=updated');
exit;