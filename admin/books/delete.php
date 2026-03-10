<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: ' . BASE_URL . 'admin/books/index.php');
    exit;
}

$stmt = $conn->prepare("SELECT cover, stock, title FROM books WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    header('Location: ' . BASE_URL . 'admin/books/index.php');
    exit;
}

// Cek stok — tidak boleh hapus kalau masih ada stok
if ($book['stock'] > 0) {
    header('Location: ' . BASE_URL . 'admin/books/index.php?msg=has_stock');
    exit;
}

// Hapus cover file
if (!empty($book['cover'])) {
    $file = __DIR__ . '/../../assets/uploads/covers/' . $book['cover'];
    if (file_exists($file)) unlink($file);
}

$stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

header('Location: ' . BASE_URL . 'admin/books/index.php?msg=deleted');
exit;