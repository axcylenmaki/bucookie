<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

// Pastikan request datang dari POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'admin/orders/index.php');
    exit;
}

$order_id   = (int)($_POST['order_id'] ?? 0);
$new_status = $_POST['status'] ?? '';
$expedition = trim($_POST['expedition'] ?? '');
$tracking   = trim($_POST['tracking_number'] ?? '');
$allowed    = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

// 1. Validasi Input Dasar
if (!$order_id || !in_array($new_status, $allowed)) {
    header('Location: ' . BASE_URL . 'admin/orders/index.php?msg=error');
    exit;
}

// 2. Ambil data pesanan saat ini untuk validasi alur
$stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$current_order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current_order) {
    header('Location: ' . BASE_URL . 'admin/orders/index.php?msg=not_found');
    exit;
}

$old_status = $current_order['status'];

// 3. Logika Keamanan Alur (Solid Flow)
// Mendefinisikan urutan status agar tidak bisa mundur
$priority = [
    'pending'    => 1,
    'processing' => 2,
    'shipped'    => 3,
    'delivered'  => 4,
    'cancelled'  => 0 // Status spesial
];

$isValid = false;

// Aturan 1: Status baru harus lebih tinggi atau sama (tidak boleh mundur)
if ($priority[$new_status] >= $priority[$old_status] && $old_status !== 'delivered' && $old_status !== 'cancelled') {
    $isValid = true;
}

// Aturan 2: Pembatalan hanya boleh dilakukan sebelum barang dikirim (shipped)
if ($new_status === 'cancelled' && $priority[$old_status] < 3 && $old_status !== 'cancelled') {
    $isValid = true;
}

// Jika status tidak berubah, tetap izinkan (untuk update resi)
if ($new_status === $old_status) {
    $isValid = true;
}

if (!$isValid) {
    header('Location: ' . BASE_URL . 'admin/orders/index.php?msg=invalid_flow');
    exit;
}

// 4. Logika Update Stok (Hanya jika berubah menjadi CANCELLED)
if ($new_status === 'cancelled' && $old_status !== 'cancelled') {
    $item_query = $conn->query("SELECT book_id, quantity FROM order_items WHERE order_id = $order_id");
    while ($item = $item_query->fetch_assoc()) {
        $update_stock = $conn->prepare("UPDATE books SET stock = stock + ? WHERE id = ?");
        $update_stock->bind_param('ii', $item['quantity'], $item['book_id']);
        $update_stock->execute();
        $update_stock->close();
    }
}

// 5. Eksekusi Update Status Pesanan
if ($new_status === 'shipped') {
    // Jika dikirim, wajib simpan ekspedisi dan resi
    $stmt = $conn->prepare("UPDATE orders SET status = ?, expedition = ?, tracking_number = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('sssi', $new_status, $expedition, $tracking, $order_id);
} else {
    // Jika bukan shipped, resi tetap dipertahankan atau tidak diubah
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('si', $new_status, $order_id);
}

if ($stmt->execute()) {
    $stmt->close();
    header('Location: ' . BASE_URL . 'admin/orders/index.php?msg=updated');
} else {
    header('Location: ' . BASE_URL . 'admin/orders/index.php?msg=error');
}
exit;