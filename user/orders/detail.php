<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireUser();

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: ' . BASE_URL . 'user/orders/index.php');
    exit;
}

$order_items = $conn->query("
    SELECT oi.*, b.title, b.author, b.cover
    FROM order_items oi
    JOIN books b ON oi.book_id = b.id
    WHERE oi.order_id = $id
");

$items = [];
while ($item = $order_items->fetch_assoc()) {
    $items[] = $item;
}

$page_title = 'Detail Pesanan #' . $id;
require_once __DIR__ . '/../../includes/header.php';

$badge_map = [
    'pending'    => ['bg'=>'rgba(245,158,11,.1)',  'color'=>'#fbbf24','label'=>'Menunggu'],
    'processing' => ['bg'=>'rgba(59,130,246,.1)',  'color'=>'#60a5fa','label'=>'Diproses'],
    'shipped'    => ['bg'=>'rgba(139,92,246,.1)',  'color'=>'#a78bfa','label'=>'Dikirim'],
    'delivered'  => ['bg'=>'rgba(34,197,94,.1)',   'color'=>'#4ade80','label'=>'Terkirim'],
    'cancelled'  => ['bg'=>'rgba(239,68,68,.1)',   'color'=>'#f87171','label'=>'Dibatalkan'],
];
$b = $badge_map[$order['status']] ?? $badge_map['pending'];
?>

<div style="margin-bottom:16px">
    <a href="<?= BASE_URL ?>user/orders/index.php" style="color:var(--text-muted);text-decoration:none;font-size:.8rem;display:inline-flex;align-items:center;gap:6px">
        <i class="bi bi-arrow-left"></i> Kembali ke Pesanan
    </a>
</div>

<!-- Header -->
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
    <div>
        <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:4px">Nomor Pesanan</div>
        <div style="font-family:'Lora',serif;font-size:1.2rem;font-weight:600">#<?= $order['id'] ?></div>
        <div style="font-size:.78rem;color:var(--text-muted);margin-top:4px">
            <i class="bi bi-calendar3"></i> <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
        </div>
    </div>
    <span style="background:<?= $b['bg'] ?>;color:<?= $b['color'] ?>;padding:6px 18px;border-radius:999px;font-size:.8rem;font-weight:500">
        <?= $b['label'] ?>
    </span>
</div>

<!-- Progress -->
<?php if ($order['status'] !== 'cancelled'): ?>
<?php
$steps = ['pending','processing','shipped','delivered'];
$cur   = array_search($order['status'], $steps);
$labels = ['Menunggu','Diproses','Dikirim','Terkirim'];
$icons  = ['bi-clock','bi-gear','bi-truck','bi-house-check'];
?>
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:16px">
    <div style="display:flex;align-items:center">
        <?php foreach ($steps as $i => $step): ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center">
            <div style="width:36px;height:36px;border-radius:50%;background:<?= $i <= $cur ? 'var(--accent)' : 'var(--bg-base)' ?>;border:2px solid <?= $i <= $cur ? 'var(--accent)' : 'var(--border)' ?>;display:flex;align-items:center;justify-content:center;color:<?= $i <= $cur ? '#fff' : 'var(--text-muted)' ?>;font-size:.85rem;position:relative;z-index:1">
                <?php if ($i < $cur): ?>
                <i class="bi bi-check-lg"></i>
                <?php else: ?>
                <i class="bi <?= $icons[$i] ?>"></i>
                <?php endif; ?>
            </div>
            <div style="font-size:.7rem;margin-top:6px;color:<?= $i <= $cur ? 'var(--text-primary)' : 'var(--text-muted)' ?>;font-weight:<?= $i === $cur ? '600' : '400' ?>;text-align:center"><?= $labels[$i] ?></div>
        </div>
        <?php if ($i < count($steps) - 1): ?>
        <div style="flex:2;height:2px;background:<?= $i < $cur ? 'var(--accent)' : 'var(--border)' ?>;margin-bottom:24px"></div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start">

    <!-- Items -->
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden">
        <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
            <h3 style="font-size:.9rem;font-weight:600;display:flex;align-items:center;gap:8px">
                <i class="bi bi-bag" style="color:var(--accent)"></i> Item Pesanan
            </h3>
        </div>
        <?php foreach ($items as $item): ?>
        <div style="padding:16px 20px;display:flex;gap:14px;align-items:center;border-bottom:1px solid var(--border)">
            <div style="width:44px;height:60px;border-radius:6px;overflow:hidden;flex-shrink:0;background:var(--bg-base);border:1px solid var(--border)">
                <?php if (!empty($item['cover']) && file_exists(__DIR__ . '/../../assets/uploads/covers/' . $item['cover'])): ?>
                <img src="<?= BASE_URL ?>assets/uploads/covers/<?= htmlspecialchars($item['cover']) ?>" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
                    <i class="bi bi-book" style="color:var(--text-muted);font-size:.8rem"></i>
                </div>
                <?php endif; ?>
            </div>
            <div style="flex:1">
                <div style="font-size:.875rem;font-weight:500;color:var(--text-primary)"><?= htmlspecialchars($item['title']) ?></div>
                <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px"><?= htmlspecialchars($item['author']) ?></div>
            </div>
            <div style="text-align:right;flex-shrink:0">
                <div style="font-size:.78rem;color:var(--text-muted)">×<?= $item['quantity'] ?></div>
                <div style="font-size:.85rem;font-weight:500;color:var(--text-primary)">Rp <?= number_format($item['price'], 0, ',', '.') ?></div>
                <div style="font-size:.8rem;font-weight:600;color:var(--accent)">Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <div style="padding:14px 20px;display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:.83rem;color:var(--text-muted)"><?= count($items) ?> buku</span>
            <div style="font-size:1rem;font-weight:600;color:var(--accent)">
                Total: Rp <?= number_format($order['total_price'], 0, ',', '.') ?>
            </div>
        </div>
    </div>

    <!-- Info -->
    <div style="display:flex;flex-direction:column;gap:12px">
        <!-- Alamat -->
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:18px">
            <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:10px;display:flex;align-items:center;gap:6px">
                <i class="bi bi-geo-alt" style="color:var(--accent)"></i> Alamat Pengiriman
            </div>
            <p style="font-size:.85rem;color:var(--text-secondary);line-height:1.6"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
        </div>
        <!-- Pembayaran -->
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:18px">
            <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:10px;display:flex;align-items:center;gap:6px">
                <i class="bi bi-credit-card" style="color:var(--accent)"></i> Pembayaran
            </div>
            <div style="font-size:.85rem;color:var(--text-secondary);display:flex;align-items:center;gap:8px">
                <i class="bi bi-truck" style="color:var(--accent)"></i> Payment at Delivery
            </div>
        </div>
        <!-- Update -->
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:18px">
            <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:6px">Terakhir diperbarui</div>
            <div style="font-size:.83rem;color:var(--text-secondary)"><?= date('d M Y, H:i', strtotime($order['updated_at'] ?? $order['created_at'])) ?></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>