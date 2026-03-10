<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: ' . BASE_URL . 'admin/categories/index.php');
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM books WHERE category_id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$has_books = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

if ($has_books > 0) {
    header('Location: ' . BASE_URL . 'admin/categories/index.php?msg=has_books');
    exit;
}

$stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

header('Location: ' . BASE_URL . 'admin/categories/index.php?msg=deleted');
exit;