<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireUser();

// Tambah ke cart
if (isset($_GET['add'])) {
    $book_id = (int)$_GET['add'];

    // Cek stok
    $s = $conn->prepare("SELECT stock FROM books WHERE id = ?");
    $s->bind_param('i', $book_id);
    $s->execute();
    $book = $s->get_result()->fetch_assoc();
    $s->close();

    if ($book && $book['stock'] > 0) {
        // Cek sudah ada di cart?
        $s = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND book_id = ?");
        $s->bind_param('ii', $_SESSION['user_id'], $book_id);
        $s->execute();
        $existing = $s->get_result()->fetch_assoc();
        $s->close();

        if ($existing) {
            $new_qty = $existing['quantity'] + 1;
            if ($new_qty <= $book['stock']) {
                $s = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $s->bind_param('ii', $new_qty, $existing['id']);
                $s->execute();
                $s->close();
            }
        } else {
            $s = $conn->prepare("INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, 1)");
            $s->bind_param('ii', $_SESSION['user_id'], $book_id);
            $s->execute();
            $s->close();
        }
    }
    header('Location: ' . BASE_URL . 'pages/book_detail.php?id=' . $book_id . '&msg=added');
    exit;
}

// Update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    foreach ($_POST['qty'] as $cart_id => $qty) {
        $cart_id = (int)$cart_id;
        $qty     = (int)$qty;
        if ($qty < 1) {
            $s = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $s->bind_param('ii', $cart_id, $_SESSION['user_id']);
        } else {
            $s = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $s->bind_param('iii', $qty, $cart_id, $_SESSION['user_id']);
        }
        $s->execute();
        $s->close();
    }
    header('Location: ' . BASE_URL . 'pages/cart.php');
    exit;
}

// Hapus item
if (isset($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    $s = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $s->bind_param('ii', $cart_id, $_SESSION['user_id']);
    $s->execute();
    $s->close();
    header('Location: ' . BASE_URL . 'pages/cart.php');
    exit;
}

// Ambil data cart
$cart_items = $conn->query("
    SELECT c.id AS cart_id, c.quantity, b.id AS book_id, b.title, b.author,
           b.price, b.cover, b.stock
    FROM cart c
    JOIN books b ON c.book_id = b.id
    WHERE c.user_id = {$_SESSION['user_id']}
    ORDER BY c.added_at DESC
");

$total = 0;
$items = [];
while ($item = $cart_items->fetch_assoc()) {
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $total += $item['subtotal'];
    $items[] = $item;
}

$page_title = 'Keranjang';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom:20px">
    <h2 style="font-family:'Lora',serif;font-size:1.3rem;font-weight:600;margin-bottom:4px">Keranjang Belanja</h2>
    <p style="font-size:.82rem;color:var(--text-secondary)"><?= count($items) ?> buku di keranjang</p>
</div>

<?php if (empty($items)): ?>
<div class="empty-state">
    <i class="bi bi-cart-x"></i>
    <p>Keranjang kamu masih kosong.<br>
       <a href="<?= BASE_URL ?>pages/books.php" style="color:var(--accent)">Mulai belanja →</a>
    </p>
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">

    <!-- Cart Items -->
    <div>
        <form method="POST">
            <input type="hidden" name="update" value="1">
            <div style="display:flex;flex-direction:column;gap:12px">
                <?php foreach ($items as $item): ?>
                <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:16px;display:flex;gap:16px;align-items:center">
                    <!-- Cover -->
                    <div style="width:52px;height:70px;border-radius:6px;overflow:hidden;flex-shrink:0;background:var(--bg-base);border:1px solid var(--border)">
                        <?php if (!empty($item['cover']) && file_exists(__DIR__ . '/../assets/uploads/covers/' . $item['cover'])): ?>
                        <img src="<?= BASE_URL ?>assets/uploads/covers/<?= htmlspecialchars($item['cover']) ?>"
                             style="width:100%;height:100%;object-fit:cover">
                        <?php else: ?>
                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
                            <i class="bi bi-book" style="color:var(--text-muted)"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- Info -->
                    <div style="flex:1">
                        <div style="font-size:.88rem;font-weight:500;color:var(--text-primary);margin-bottom:3px"><?= htmlspecialchars($item['title']) ?></div>
                        <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:8px"><?= htmlspecialchars($item['author']) ?></div>
                        <div style="font-size:.85rem;font-weight:600;color:var(--accent)">Rp <?= number_format($item['price'], 0, ',', '.') ?></div>
                    </div>
                    <!-- Qty -->
                    <div style="display:flex;align-items:center;gap:8px">
                        <input type="number" name="qty[<?= $item['cart_id'] ?>]"
                               value="<?= $item['quantity'] ?>"
                               min="0" max="<?= $item['stock'] ?>"
                               style="width:60px;background:var(--bg-base);border:1px solid var(--border);border-radius:6px;padding:6px 8px;color:var(--text-primary);font-family:'Sora',sans-serif;font-size:.85rem;text-align:center;outline:none">
                    </div>
                    <!-- Subtotal -->
                    <div style="text-align:right;min-width:110px">
                        <div style="font-size:.85rem;font-weight:600;color:var(--text-primary)">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></div>
                        <a href="<?= BASE_URL ?>pages/cart.php?remove=<?= $item['cart_id'] ?>"
                           style="font-size:.72rem;color:var(--danger);text-decoration:none;margin-top:4px;display:inline-block"
                           onclick="return confirm('Hapus buku ini dari keranjang?')">
                            <i class="bi bi-trash"></i> Hapus
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" style="margin-top:14px;padding:8px 18px;background:var(--bg-card);border:1px solid var(--border);color:var(--text-secondary);border-radius:8px;font-family:'Sora',sans-serif;font-size:.8rem;cursor:pointer">
                <i class="bi bi-arrow-clockwise"></i> Update Keranjang
            </button>
        </form>
    </div>

    <!-- Summary -->
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:20px;position:sticky;top:80px">
        <h3 style="font-family:'Lora',serif;font-size:1rem;font-weight:600;margin-bottom:16px">Ringkasan Pesanan</h3>
        <div style="display:flex;justify-content:space-between;font-size:.83rem;color:var(--text-secondary);margin-bottom:8px">
            <span>Subtotal (<?= count($items) ?> buku)</span>
            <span>Rp <?= number_format($total, 0, ',', '.') ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.83rem;color:var(--text-secondary);margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--border)">
            <span>Pengiriman</span>
            <span style="color:#4ade80">Gratis</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:1rem;font-weight:600;color:var(--text-primary);margin-bottom:20px">
            <span>Total</span>
            <span style="color:var(--accent)">Rp <?= number_format($total, 0, ',', '.') ?></span>
        </div>
        <div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:8px;padding:10px 12px;font-size:.75rem;color:#fbbf24;margin-bottom:16px;display:flex;gap:8px">
            <i class="bi bi-truck" style="flex-shrink:0;margin-top:1px"></i>
            <span>Pembayaran dilakukan saat buku tiba (<b>Payment at Delivery</b>)</span>
        </div>
        <a href="<?= BASE_URL ?>user/orders/checkout.php"
           style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:11px;background:var(--accent);color:#fff;border-radius:8px;text-decoration:none;font-size:.875rem;font-weight:500;transition:all .2s">
            <i class="bi bi-bag-check"></i> Checkout Sekarang
        </a>
        <a href="<?= BASE_URL ?>pages/books.php"
           style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:10px;background:transparent;border:1px solid var(--border);color:var(--text-secondary);border-radius:8px;text-decoration:none;font-size:.82rem;margin-top:8px">
            <i class="bi bi-arrow-left"></i> Lanjut Belanja
        </a>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>