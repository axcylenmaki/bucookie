<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$id   = (int)($_GET['id'] ?? 0);
$msg  = $_GET['msg'] ?? '';

$stmt = $conn->prepare("
    SELECT b.*, c.name AS category_name
    FROM books b
    JOIN categories c ON b.category_id = c.id
    WHERE b.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    header('Location: ' . BASE_URL . 'pages/books.php');
    exit;
}

$page_title = $book['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom:16px">
    <a href="<?= BASE_URL ?>pages/books.php" style="color:var(--text-muted);text-decoration:none;font-size:.8rem;display:inline-flex;align-items:center;gap:6px">
        <i class="bi bi-arrow-left"></i> Kembali ke Koleksi
    </a>
</div>

<?php if ($msg === 'added'): ?>
<div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);color:#4ade80;border-radius:8px;padding:10px 16px;font-size:.82rem;margin-bottom:20px;display:flex;align-items:center;gap:8px">
    <i class="bi bi-check-circle"></i> Buku berhasil ditambahkan ke keranjang!
    <a href="<?= BASE_URL ?>pages/cart.php" style="color:#4ade80;margin-left:4px;font-weight:500">Lihat keranjang →</a>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:220px 1fr;gap:32px;align-items:start">

    <!-- Cover -->
    <div>
        <div style="width:100%;aspect-ratio:3/4;background:linear-gradient(145deg,#1a2d42,#0f1e30);border-radius:12px;overflow:hidden;border:1px solid var(--border)">
            <?php if (!empty($book['cover']) && file_exists(__DIR__ . '/../assets/uploads/covers/' . $book['cover'])): ?>
                <img src="<?= BASE_URL ?>assets/uploads/covers/<?= htmlspecialchars($book['cover']) ?>"
                     style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
                <div style="width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px">
                    <i class="bi bi-book-half" style="font-size:3rem;color:var(--text-muted)"></i>
                    <span style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em">No Cover</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Stok badge -->
        <div style="margin-top:12px;text-align:center">
            <?php if ($book['stock'] > 0): ?>
            <span style="background:rgba(34,197,94,.1);color:#4ade80;border:1px solid rgba(34,197,94,.2);padding:4px 14px;border-radius:999px;font-size:.75rem">
                <i class="bi bi-check-circle"></i> Stok tersedia (<?= $book['stock'] ?>)
            </span>
            <?php else: ?>
            <span style="background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.2);padding:4px 14px;border-radius:999px;font-size:.75rem">
                <i class="bi bi-x-circle"></i> Stok habis
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info -->
    <div>
        <div style="font-size:.72rem;color:var(--accent);text-transform:uppercase;letter-spacing:.1em;margin-bottom:8px">
            <?= htmlspecialchars($book['category_name']) ?>
        </div>
        <h1 style="font-family:'Lora',serif;font-size:1.7rem;font-weight:600;line-height:1.25;margin-bottom:8px">
            <?= htmlspecialchars($book['title']) ?>
        </h1>
        <div style="font-size:.88rem;color:var(--text-secondary);margin-bottom:20px">
            <?= htmlspecialchars($book['author']) ?>
            <?php if ($book['publisher']): ?> · <?= htmlspecialchars($book['publisher']) ?><?php endif; ?>
            <?php if ($book['year']): ?> · <?= $book['year'] ?><?php endif; ?>
        </div>

        <div style="font-family:'Lora',serif;font-size:1.8rem;font-weight:600;color:var(--accent);margin-bottom:24px">
            Rp <?= number_format($book['price'], 0, ',', '.') ?>
        </div>

        <?php if ($book['description']): ?>
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:24px">
            <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:8px">Deskripsi</div>
            <p style="font-size:.875rem;color:var(--text-secondary);line-height:1.7"><?= nl2br(htmlspecialchars($book['description'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Action -->
        <?php if ($book['stock'] > 0): ?>
            <?php if (isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>pages/cart.php?add=<?= $book['id'] ?>"
               style="display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:var(--accent);color:#fff;border-radius:8px;text-decoration:none;font-size:.875rem;font-weight:500;transition:all .2s">
                <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
            </a>
            <?php else: ?>
            <a href="<?= BASE_URL ?>auth/login.php"
               style="display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:var(--accent);color:#fff;border-radius:8px;text-decoration:none;font-size:.875rem;font-weight:500">
                <i class="bi bi-box-arrow-in-right"></i> Masuk untuk Beli
            </a>
            <?php endif; ?>
        <?php else: ?>
        <button disabled style="display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:var(--bg-card);color:var(--text-muted);border:1px solid var(--border);border-radius:8px;font-size:.875rem;cursor:not-allowed">
            <i class="bi bi-x-circle"></i> Stok Habis
        </button>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>