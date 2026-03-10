<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

// Stats
$total_books    = (int)$conn->query("SELECT COUNT(*) AS c FROM books")->fetch_assoc()['c'];
$total_users    = (int)$conn->query("SELECT COUNT(*) AS c FROM users WHERE role='user'")->fetch_assoc()['c'];
$total_orders   = (int)$conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];
$total_revenue  = (float)$conn->query("SELECT COALESCE(SUM(total_price),0) AS c FROM orders WHERE status != 'cancelled'")->fetch_assoc()['c'];

// Pesanan terbaru
$recent_orders = $conn->query("
    SELECT o.id, o.total_price, o.status, o.created_at, u.name AS user_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 8
");

// Buku stok menipis (stok <= 5)
$low_stock = $conn->query("
    SELECT b.id, b.title, b.stock, c.name AS category_name
    FROM books b
    JOIN categories c ON b.category_id = c.id
    WHERE b.stock <= 5
    ORDER BY b.stock ASC
    LIMIT 6
");
?>

<div class="page-header">
    <h1>Dashboard</h1>
    <p>Selamat datang kembali, <?= htmlspecialchars($_SESSION['user_name']) ?>!</p>
</div>

<!-- STAT CARDS -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-book"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= number_format($total_books) ?></div>
            <div class="stat-label">Total Buku</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-people"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= number_format($total_users) ?></div>
            <div class="stat-label">Total User</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="bi bi-bag-check"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= number_format($total_orders) ?></div>
            <div class="stat-label">Total Pesanan</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-cash-stack"></i></div>
        <div class="stat-info">
            <div class="stat-num">Rp <?= number_format($total_revenue, 0, ',', '.') ?></div>
            <div class="stat-label">Total Pendapatan</div>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start;">

    <!-- PESANAN TERBARU -->
    <div>
        <div class="table-card">
            <div class="table-card-header">
                <h2><i class="bi bi-bag-check me-2"></i>Pesanan Terbaru</h2>
                <a href="<?= BASE_URL ?>admin/orders/index.php" class="btn-action btn-view">
                    Lihat semua <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="table-responsive">
                <?php if ($recent_orders->num_rows === 0): ?>
                <div class="empty-state">
                    <i class="bi bi-bag-x"></i>
                    <p>Belum ada pesanan</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>User</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td class="td-bold">#<?= $order['id'] ?></td>
                            <td><?= htmlspecialchars($order['user_name']) ?></td>
                            <td class="td-bold">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>
                            <td>
                                <span class="badge-status badge-<?= $order['status'] ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- STOK MENIPIS -->
    <div>
        <div class="table-card">
            <div class="table-card-header">
                <h2><i class="bi bi-exclamation-triangle me-2" style="color:var(--warning)"></i>Stok Menipis</h2>
                <a href="<?= BASE_URL ?>admin/books/index.php" class="btn-action btn-view">
                    Kelola <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="table-responsive">
                <?php if ($low_stock->num_rows === 0): ?>
                <div class="empty-state">
                    <i class="bi bi-check-circle" style="color:var(--success)"></i>
                    <p>Semua stok aman</p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($book = $low_stock->fetch_assoc()): ?>
                        <tr>
                            <td class="td-bold" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                <?= htmlspecialchars($book['title']) ?>
                            </td>
                            <td><?= htmlspecialchars($book['category_name']) ?></td>
                            <td>
                                <span class="badge-status <?= $book['stock'] == 0 ? 'badge-cancelled' : 'badge-pending' ?>">
                                    <?= $book['stock'] == 0 ? 'Habis' : $book['stock'] . ' tersisa' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>