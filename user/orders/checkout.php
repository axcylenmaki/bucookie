<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireUser();

// Ambil cart
$cart_items = $conn->query("
    SELECT c.id AS cart_id, c.quantity, b.id AS book_id, b.title,
           b.price, b.stock
    FROM cart c
    JOIN books b ON c.book_id = b.id
    WHERE c.user_id = {$_SESSION['user_id']}
");

$items = [];
$total = 0;
while ($item = $cart_items->fetch_assoc()) {
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $total += $item['subtotal'];
    $items[] = $item;
}

if (empty($items)) {
    header('Location: ' . BASE_URL . 'pages/cart.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');

    if (empty($address)) {
        $error = 'Alamat pengiriman wajib diisi.';
    } else {
        // Validasi stok ulang
        $stock_error = false;
        foreach ($items as $item) {
            if ($item['quantity'] > $item['stock']) {
                $stock_error = true;
                $error = 'Stok buku "' . htmlspecialchars($item['title']) . '" tidak mencukupi.';
                break;
            }
        }

        if (!$stock_error) {
            // Insert order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, shipping_address) VALUES (?, ?, ?)");
            $stmt->bind_param('ids', $_SESSION['user_id'], $total, $address);
            $stmt->execute();
            $order_id = $stmt->insert_id;
            $stmt->close();

            // Insert order items & kurangi stok
            foreach ($items as $item) {
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('iiid', $order_id, $item['book_id'], $item['quantity'], $item['price']);
                $stmt->execute();
                $stmt->close();

                $new_stock = $item['stock'] - $item['quantity'];
                $stmt = $conn->prepare("UPDATE books SET stock = ? WHERE id = ?");
                $stmt->bind_param('ii', $new_stock, $item['book_id']);
                $stmt->execute();
                $stmt->close();
            }

            // Kosongkan cart
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();

            // Notifikasi untuk admin
            $notif_msg = "Pesanan baru #" . $order_id . " dari " . $_SESSION['user_name'];
            $stmt_n = $conn->prepare("INSERT INTO notifications (type, reference_id, message) VALUES ('order', ?, ?)");
            $stmt_n->bind_param('is', $order_id, $notif_msg);
            $stmt_n->execute();
            $stmt_n->close();

            header('Location: ' . BASE_URL . 'user/orders/index.php?msg=success&order_id=' . $order_id);
            exit;
        }
    }
}

// Ambil alamat user sebagai default
$user = $conn->query("SELECT address FROM users WHERE id = {$_SESSION['user_id']}")->fetch_assoc();

$page_title = 'Checkout';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom:20px">
    <a href="<?= BASE_URL ?>pages/cart.php" style="color:var(--text-muted);text-decoration:none;font-size:.8rem;display:inline-flex;align-items:center;gap:6px">
        <i class="bi bi-arrow-left"></i> Kembali ke Keranjang
    </a>
</div>

<h2 style="font-family:'Lora',serif;font-size:1.3rem;font-weight:600;margin-bottom:20px">Checkout</h2>

<?php if ($error): ?>
<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#f87171;border-radius:8px;padding:10px 16px;font-size:.82rem;margin-bottom:20px;display:flex;gap:8px">
    <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">

    <!-- Form Checkout -->
    <form method="POST">
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:16px">
            <h3 style="font-size:.9rem;font-weight:600;margin-bottom:14px;display:flex;align-items:center;gap:8px">
                <i class="bi bi-geo-alt" style="color:var(--accent)"></i> Alamat Pengiriman
            </h3>
            <textarea name="address" rows="3"
                      style="width:100%;background:var(--bg-base);border:1px solid var(--border);border-radius:8px;padding:10px 13px;color:var(--text-primary);font-family:'Sora',sans-serif;font-size:.875rem;outline:none;resize:vertical;transition:border-color .2s"
                      placeholder="Masukkan alamat lengkap pengiriman..."
                      required><?= htmlspecialchars($_POST['address'] ?? $user['address'] ?? '') ?></textarea>
        </div>

        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:16px">
            <h3 style="font-size:.9rem;font-weight:600;margin-bottom:14px;display:flex;align-items:center;gap:8px">
                <i class="bi bi-credit-card" style="color:var(--accent)"></i> Metode Pembayaran
            </h3>
            <div style="background:var(--bg-base);border:1px solid var(--accent);border-radius:8px;padding:12px 16px;display:flex;align-items:center;gap:10px">
                <i class="bi bi-truck" style="color:var(--accent);font-size:1.1rem"></i>
                <div>
                    <div style="font-size:.85rem;font-weight:500;color:var(--text-primary)">Payment at Delivery</div>
                    <div style="font-size:.72rem;color:var(--text-muted)">Bayar saat buku tiba di tangan kamu</div>
                </div>
                <i class="bi bi-check-circle-fill" style="color:var(--accent);margin-left:auto"></i>
            </div>
        </div>

        <button type="submit"
                style="width:100%;padding:12px;background:var(--accent);color:#fff;border:none;border-radius:8px;font-family:'Sora',sans-serif;font-size:.9rem;font-weight:500;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px">
            <i class="bi bi-bag-check"></i> Buat Pesanan — Rp <?= number_format($total, 0, ',', '.') ?>
        </button>
    </form>

    <!-- Order Summary -->
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:20px;position:sticky;top:80px">
        <h3 style="font-family:'Lora',serif;font-size:1rem;font-weight:600;margin-bottom:14px">Ringkasan (<?= count($items) ?> buku)</h3>
        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px">
            <?php foreach ($items as $item): ?>
            <div style="display:flex;justify-content:space-between;font-size:.82rem;gap:10px">
                <span style="color:var(--text-secondary)">
                    <?= htmlspecialchars($item['title']) ?>
                    <span style="color:var(--text-muted)"> ×<?= $item['quantity'] ?></span>
                </span>
                <span style="color:var(--text-primary);font-weight:500;white-space:nowrap">
                    Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="border-top:1px solid var(--border);padding-top:12px;display:flex;justify-content:space-between;font-size:1rem;font-weight:600">
            <span>Total</span>
            <span style="color:var(--accent)">Rp <?= number_format($total, 0, ',', '.') ?></span>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>