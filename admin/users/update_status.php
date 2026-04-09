<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

$order_id   = (int)($_POST['order_id'] ?? 0);
$new_status = $_POST['status'] ?? '';
$expedition = trim($_POST['expedition'] ?? '');
$tracking   = trim($_POST['tracking_number'] ?? '');
$allowed    = ['pending','processing','shipped','delivered','cancelled'];

if (!$order_id || !in_array($new_status, $allowed)) {
    header('Location: ' . BASE_URL . 'admin/orders/index.php');
    exit;
}

// 1. Ambil status lama dari database
$stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: ' . BASE_URL . 'admin/orders/index.php');
    exit;
}

$old_status = $order['status'];

// 2. DEFISINI PRIORITAS (SOLID FLOW)
// Semakin tinggi angka, semakin "akhir" tahapannya
$priority = [
    'pending'    => 1,
    'processing' => 2,
    'shipped'    => 3,
    'delivered'  => 4,
    'cancelled'  => 0 // Special case
];

// 3. VALIDASI KEAMANAN SERVER
$isValid = false;

// Jika status baru bobotnya lebih tinggi atau sama, diperbolehkan
if ($priority[$new_status] >= $priority[$old_status] && $old_status !== 'cancelled' && $old_status !== 'delivered') {
    $isValid = true;
}

// Khusus pembatalan: Hanya boleh jika status sebelumnya belum dikirim (priority < 3)
if ($new_status === 'cancelled' && $priority[$old_status] < 3 && $old_status !== 'cancelled') {
    $isValid = true;
}

// Jika mencoba menurunkan status (misal dari shipped ke pending), blokir
if (!$isValid && $new_status !== $old_status) {
    header('Location: ' . BASE_URL . 'admin/orders/index.php?msg=invalid_flow');
    exit;
}

// 4. LOGIKA STOK (HANYA JIKA STATUS BERUBAH KE CANCELLED)
if ($new_status === 'cancelled' && $old_status !== 'cancelled') {
    $items = $conn->query("SELECT book_id, quantity FROM order_items WHERE order_id = $order_id");
    while ($item = $items->fetch_assoc()) {
        $stmtStock = $conn->prepare("UPDATE books SET stock = stock + ? WHERE id = ?");
        $stmtStock->bind_param('ii', $item['quantity'], $item['book_id']);
        $stmtStock->execute();
        $stmtStock->close();
    }
}

// 5. UPDATE DATABASE
if ($new_status === 'shipped' && $expedition && $tracking) {
    $stmt = $conn->prepare("UPDATE orders SET status=?, expedition=?, tracking_number=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param('sssi', $new_status, $expedition, $tracking, $order_id);
} else {
    $stmt = $conn->prepare("UPDATE orders SET status=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param('si', $new_status, $order_id);
}

if ($stmt->execute()) {
    $stmt->close();
    header('Location: ' . BASE_URL . 'admin/orders/index.php?msg=updated');
} else {
    header('Location: ' . BASE_URL . 'admin/orders/index.php?msg=error');
}
exit;