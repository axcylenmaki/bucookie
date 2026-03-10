<?php
session_start();
require_once 'config/db.php';
require_once 'includes/auth_check.php';

$page_title = 'Beranda';

// Stats hero
$total_books = $conn->query("SELECT COUNT(*) AS total FROM books WHERE stock > 0")->fetch_assoc()['total'];
$total_cats  = $conn->query("SELECT COUNT(*) AS total FROM categories")->fetch_assoc()['total'];

// Cart count (dipakai header)
$cart_count = 0;
if (isLoggedIn()) {
    $cart_count = (int)($conn->query("SELECT SUM(quantity) AS total FROM cart WHERE user_id = {$_SESSION['user_id']}")->fetch_assoc()['total'] ?? 0);
}

require_once 'includes/header.php';
?>

<!-- HERO -->
<section class="hero">
    <div class="hero-eyebrow">Selamat Datang</div>
    <h1>Temukan buku <em>favoritmu</em> di sini.</h1>
    <p>Koleksi buku pilihan dari berbagai kategori. Pesan sekarang, bayar saat buku tiba di tanganmu.</p>
    <a href="<?= BASE_URL ?>pages/books.php" class="btn-primary-custom">
        <i class="bi bi-grid"></i> Lihat Koleksi
    </a>
    <?php if (!isLoggedIn()): ?>
    <a href="<?= BASE_URL ?>auth/register.php" class="btn-ghost">
        Daftar Gratis <i class="bi bi-arrow-right"></i>
    </a>
    <?php endif; ?>
    <div class="hero-stat">
        <div class="stat-item">
            <div class="stat-num"><?= number_format($total_books) ?>+</div>
            <div class="stat-label">Judul Buku</div>
        </div>
        <div class="stat-item">
            <div class="stat-num"><?= number_format($total_cats) ?>+</div>
            <div class="stat-label">Kategori</div>
        </div>
    </div>
</section>

<!-- CATEGORY PILLS -->
<?php
$active_cat = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$cat_query  = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
?>
<div class="cat-pills">
    <a href="<?= BASE_URL ?>index.php" class="cat-pill <?= $active_cat === 0 ? 'active' : '' ?>">Semua</a>
    <?php while ($cat = $cat_query->fetch_assoc()): ?>
    <a href="<?= BASE_URL ?>index.php?category=<?= $cat['id'] ?>"
       class="cat-pill <?= $active_cat === $cat['id'] ? 'active' : '' ?>">
        <?= htmlspecialchars($cat['name']) ?>
    </a>
    <?php endwhile; ?>
</div>

<!-- BOOKS SECTION -->
<div class="section-header">
    <div class="section-title">Buku Terbaru</div>
    <a href="<?= BASE_URL ?>pages/books.php" class="section-link">
        Lihat semua <i class="bi bi-arrow-right"></i>
    </a>
</div>

<div class="books-grid">
    <?php
    $where = $active_cat ? "WHERE b.category_id = $active_cat" : "";
    $books = $conn->query("
        SELECT b.id, b.title, b.author, b.price, b.cover, b.stock, b.created_at,
               c.name AS category_name
        FROM books b
        JOIN categories c ON b.category_id = c.id
        $where
        ORDER BY b.created_at DESC
        LIMIT 12
    ");

    if ($books->num_rows === 0):
    ?>
    <div class="empty-state" style="grid-column:1/-1">
        <i class="bi bi-book"></i>
        <p>Belum ada buku tersedia.</p>
    </div>
    <?php else: while ($book = $books->fetch_assoc()): ?>
    <a href="<?= BASE_URL ?>pages/book_detail.php?id=<?= $book['id'] ?>" class="book-card">
        <div class="book-cover">
            <?php if (!empty($book['cover']) && file_exists(__DIR__ . '/assets/uploads/covers/' . $book['cover'])): ?>
            <img src="<?= BASE_URL ?>assets/uploads/covers/<?= htmlspecialchars($book['cover']) ?>"
                 alt="<?= htmlspecialchars($book['title']) ?>">
            <?php else: ?>
            <div class="book-cover-placeholder">
                <i class="bi bi-book-half"></i>
                <span>No Cover</span>
            </div>
            <?php endif; ?>
            <?php
            $diff = (new DateTime())->diff(new DateTime($book['created_at']))->days;
            if ($diff <= 30):
            ?>
            <span class="book-badge">Baru</span>
            <?php endif; ?>
        </div>
        <div class="book-info">
            <div class="book-category"><?= htmlspecialchars($book['category_name']) ?></div>
            <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
            <div class="book-author"><?= htmlspecialchars($book['author']) ?></div>
            <div class="book-footer">
                <div class="book-price">Rp <?= number_format($book['price'], 0, ',', '.') ?></div>
                <?php if ($book['stock'] > 0): ?>
                <a href="<?= BASE_URL ?>pages/cart.php?add=<?= $book['id'] ?>"
                   class="btn-cart"
                   onclick="event.stopPropagation()"
                   title="Tambah ke keranjang">
                    <i class="bi bi-cart-plus"></i>
                </a>
                <?php else: ?>
                <span class="btn-cart" style="opacity:.4;cursor:not-allowed" title="Stok habis">
                    <i class="bi bi-x-lg"></i>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </a>
    <?php endwhile; endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>