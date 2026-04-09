<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Koleksi Buku';
$search     = trim($_GET['search']    ?? '');
$cat_id     = (int)($_GET['category'] ?? 0);

$where = "WHERE 1=1";
if ($search) {
    $s     = $conn->real_escape_string($search);
    $where .= " AND (b.title LIKE '%$s%' OR b.author LIKE '%$s%')";
}
if ($cat_id) {
    $where .= " AND b.category_id = $cat_id";
}

$books      = $conn->query("
    SELECT b.*, c.name AS category_name
    FROM books b
    JOIN categories c ON b.category_id = c.id
    $where
    ORDER BY b.created_at DESC
");
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom:20px">
    <h2 style="font-family:'Lora',serif;font-size:1.3rem;font-weight:600;margin-bottom:4px">Koleksi Buku</h2>
    <p style="font-size:.82rem;color:var(--text-secondary)">
        <?= $search ? 'Hasil pencarian untuk "<b>' . htmlspecialchars($search) . '</b>"' : 'Semua buku yang tersedia' ?>
    </p>
</div>

<form method="GET" style="display:flex;gap:8px;margin-bottom:20px">
    <?php if ($cat_id): ?><input type="hidden" name="category" value="<?= $cat_id ?>"><?php endif; ?>
    <div style="position:relative;flex:1;max-width:400px">
        <i class="bi bi-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.9rem"></i>
        <input type="text" name="search"
               style="width:100%;background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:9px 16px 9px 36px;color:var(--text-primary);font-family:'Sora',sans-serif;font-size:.85rem;outline:none;transition:border-color .2s"
               placeholder="Cari judul, penulis..."
               value="<?= htmlspecialchars($search) ?>">
    </div>
    <button type="submit" style="padding:9px 18px;background:var(--accent);color:#fff;border:none;border-radius:8px;font-family:'Sora',sans-serif;font-size:.82rem;cursor:pointer;font-weight:500">Cari</button>
    <?php if ($search || $cat_id): ?>
    <a href="<?= BASE_URL ?>pages/books.php" style="padding:9px 14px;background:var(--bg-card);border:1px solid var(--border);color:var(--text-secondary);border-radius:8px;text-decoration:none;font-size:.82rem;display:flex;align-items:center">Reset</a>
    <?php endif; ?>
</form>

<div class="cat-pills">
    <a href="<?= BASE_URL ?>pages/books.php<?= $search ? '?search='.urlencode($search) : '' ?>"
       class="cat-pill <?= !$cat_id ? 'active' : '' ?>">Semua</a>
    <?php while ($cat = $categories->fetch_assoc()): ?>
    <a href="<?= BASE_URL ?>pages/books.php?category=<?= $cat['id'] ?><?= $search ? '&search='.urlencode($search) : '' ?>"
       class="cat-pill <?= $cat_id === $cat['id'] ? 'active' : '' ?>">
         <?= htmlspecialchars($cat['name']) ?>
    </a>
    <?php endwhile; ?>
</div>

<div class="books-grid" style="margin-top:20px">
    <?php if ($books->num_rows === 0): ?>
    <div class="empty-state" style="grid-column:1/-1">
        <i class="bi bi-book"></i>
        <p>Tidak ada buku<?= $search ? ' untuk "<b>'.htmlspecialchars($search).'</b>"' : '' ?></p>
    </div>
    <?php else: while ($book = $books->fetch_assoc()): ?>
    <a href="<?= BASE_URL ?>pages/book_detail.php?id=<?= $book['id'] ?>" class="book-card">
        <div class="book-cover">
            <?php if (!empty($book['cover']) && file_exists(__DIR__ . '/../assets/uploads/covers/' . $book['cover'])): ?>
                <img src="<?= BASE_URL ?>assets/uploads/covers/<?= htmlspecialchars($book['cover']) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
            <?php else: ?>
                <div class="book-cover-placeholder"><i class="bi bi-book-half"></i><span>No Cover</span></div>
            <?php endif; ?>
            
            <?php if ((new DateTime())->diff(new DateTime($book['created_at']))->days <= 30): ?>
                <span class="book-badge">Baru</span>
            <?php endif; ?>
            
            <?php if ($book['stock'] == 0): ?>
                <span class="book-badge" style="background:var(--danger);top:auto;bottom:10px">Habis</span>
            <?php endif; ?>
        </div>
        <div class="book-info">
            <div class="book-category"><?= htmlspecialchars($book['category_name']) ?></div>
            <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
            <div class="book-author"><?= htmlspecialchars($book['author']) ?></div>
            <div class="book-footer">
                <div class="book-price">Rp <?= number_format($book['price'], 0, ',', '.') ?></div>
                
                <?php if ($book['stock'] > 0): ?>
                    <?php 
                        // Cek apakah user sudah login atau belum
                        if (isLoggedIn()) {
                            $cart_link = BASE_URL . "pages/cart.php?add=" . $book['id'];
                            $btn_title = "Tambah ke keranjang";
                        } else {
                            $cart_link = BASE_URL . "auth/login.php";
                            $btn_title = "Login untuk belanja";
                        }
                    ?>
                    <a href="<?= $cart_link ?>" 
                       class="btn-cart" 
                       onclick="event.stopPropagation()" 
                       title="<?= $btn_title ?>">
                        <i class="bi bi-cart-plus"></i>
                    </a>
                <?php else: ?>
                    <span class="btn-cart" style="opacity:.4;cursor:not-allowed">
                        <i class="bi bi-x-lg"></i>
                    </span>
                <?php endif; ?>

            </div>
        </div>
    </a>
    <?php endwhile; endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>