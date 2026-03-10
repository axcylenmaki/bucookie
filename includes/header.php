<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn)) require_once __DIR__ . '/../config/db.php';

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

// Cart count
$cart_count = 0;
if (isLoggedIn()) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) AS total FROM cart WHERE user_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $cart_count = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}

if (!function_exists('navActive')) {
    function navActive(string $page, string $dir = ''): string {
        global $current_page, $current_dir;
        if ($dir && $current_dir === $dir) return 'active';
        if ($current_page === $page) return 'active';
        return '';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — Bucookie' : 'Bucookie — Toko Buku' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main-wrapper">

    <header class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <form action="<?= BASE_URL ?>pages/books.php" method="GET" style="margin:0">
                <div class="search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search"
                           placeholder="Cari judul, penulis..."
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
            </form>
        </div>
        <div class="topbar-actions">
            <?php if (isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>pages/cart.php" class="btn-icon" title="Keranjang">
                <i class="bi bi-cart3"></i>
                <?php if ($cart_count > 0): ?>
                <span class="badge-dot"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
            <?php else: ?>
            <a href="<?= BASE_URL ?>auth/login.php" class="btn-login">Masuk</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="page-content">