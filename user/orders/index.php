<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireUser();

$msg      = $_GET['msg']      ?? '';
$order_id = (int)($_GET['order_id'] ?? 0);

$orders = $conn->query("
    SELECT o.*, COUNT(oi.id) AS total_items
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.user_id = {$_SESSION['user_id']}
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

$page_title = 'Pesanan Saya';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom:20px">
    <h2 style="font-family:'Lora',serif;font-size:1.3rem;font-weight:600;margin-bottom:4px">Pesanan Saya</h2>
    <p style="font-size:.82rem;color:var(--text-secondary)">Riwayat semua pesanan kamu</p>
</div>

<?php if ($msg === 'success' && $order_id): ?>
<div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);color:#4ade80;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px">
    <i class="bi bi-check-circle-fill" style="font-size:1.2rem"></i>
    <div>
        <div style="font-weight:500;font-size:.9rem">Pesanan #<?= $order_id ?> berhasil dibuat!</div>
        <div style="font-size:.78rem;margin-top:2px;opacity:.8">Kami akan segera memproses pesananmu. Pembayaran dilakukan saat buku tiba.</div>
    </div>
</div>
<?php endif; ?>

<?php if ($msg === 'cancelled' && $order_id): ?>
<div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);color:#f87171;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px">
    <i class="bi bi-x-circle-fill" style="font-size:1.2rem"></i>
    <div>
        <div style="font-weight:500;font-size:.9rem">Pesanan #<?= $order_id ?> berhasil dibatalkan.</div>
        <div style="font-size:.78rem;margin-top:2px;opacity:.8">Stok buku telah dikembalikan.</div>
    </div>
</div>
<?php endif; ?>

<?php if ($msg === 'cancel_failed'): ?>
<div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);color:#f87171;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px">
    <i class="bi bi-exclamation-circle-fill" style="font-size:1.2rem"></i>
    <div style="font-size:.9rem">Pesanan tidak bisa dibatalkan karena sudah diproses.</div>
</div>
<?php endif; ?>

<?php if ($orders->num_rows === 0): ?>
<div style="text-align:center;padding:60px 20px;color:var(--text-muted)">
    <i class="bi bi-bag-x" style="font-size:2.5rem;margin-bottom:12px;display:block"></i>
    <p style="font-size:.85rem">Belum ada pesanan.<br>
       <a href="<?= BASE_URL ?>pages/books.php" style="color:var(--accent)">Mulai belanja →</a>
    </p>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:12px">
    <?php while ($order = $orders->fetch_assoc()): ?>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden">
        <div style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
            <div style="display:flex;align-items:center;gap:16px">
                <div>
                    <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:2px">Pesanan</div>
                    <div style="font-weight:600;color:var(--text-primary)">#<?= $order['id'] ?></div>
                </div>
                <div>
                    <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:2px">Tanggal</div>
                    <div style="font-size:.83rem;color:var(--text-secondary)"><?= date('d M Y', strtotime($order['created_at'])) ?></div>
                </div>
                <div>
                    <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:2px">Items</div>
                    <div style="font-size:.83rem;color:var(--text-secondary)"><?= $order['total_items'] ?> buku</div>
                </div>
                <div>
                    <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:2px">Total</div>
                    <div style="font-size:.9rem;font-weight:600;color:var(--accent)">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <?php
                $badge_map = [
                    'pending'    => ['bg'=>'rgba(245,158,11,.1)','color'=>'#fbbf24','label'=>'Menunggu'],
                    'processing' => ['bg'=>'rgba(59,130,246,.1)','color'=>'#60a5fa','label'=>'Diproses'],
                    'shipped'    => ['bg'=>'rgba(139,92,246,.1)', 'color'=>'#a78bfa','label'=>'Dikirim'],
                    'delivered'  => ['bg'=>'rgba(34,197,94,.1)',  'color'=>'#4ade80','label'=>'Terkirim'],
                    'cancelled'  => ['bg'=>'rgba(239,68,68,.1)',  'color'=>'#f87171','label'=>'Dibatalkan'],
                ];
                $b = $badge_map[$order['status']] ?? $badge_map['pending'];
                ?>
                <span style="background:<?= $b['bg'] ?>;color:<?= $b['color'] ?>;padding:4px 12px;border-radius:999px;font-size:.72rem;font-weight:500">
                    <?= $b['label'] ?>
                </span>

                <a href="<?= BASE_URL ?>user/orders/detail.php?id=<?= $order['id'] ?>"
                   style="padding:6px 14px;background:var(--accent-soft);color:var(--accent);border-radius:7px;text-decoration:none;font-size:.78rem;font-weight:500">
                    Detail <i class="bi bi-arrow-right"></i>
                </a>

                <?php if ($order['status'] === 'pending'): ?>
                <button onclick="confirmCancel(<?= $order['id'] ?>)"
                        style="padding:6px 14px;background:rgba(239,68,68,.08);color:#f87171;border:1px solid rgba(239,68,68,.2);border-radius:7px;font-size:.78rem;font-weight:500;cursor:pointer">
                    <i class="bi bi-x-circle"></i> Batalkan
                </button>
                <?php endif; ?>

                <?php if (in_array($order['status'], ['shipped','delivered']) && !empty($order['tracking_number'])): ?>
                <?php
                    $exp_urls = [
                        'jne'      => 'https://www.jne.co.id/id/tracking/trace/',
                        'jnt'      => 'https://www.jet.co.id/track/',
                        'sicepat'  => 'https://www.sicepat.com/checkAwb/',
                        'pos'      => 'https://www.posindonesia.co.id/id/tracking/',
                        'tiki'     => 'https://tiki.id/id/tracking?awb=',
                        'anteraja' => 'https://anteraja.id/tracking/',
                        'ninja'    => 'https://www.ninjaxpress.co/id-id/tracking?id=',
                    ];
                    $exp_names = [
                        'jne'=>'JNE','jnt'=>'J&T','sicepat'=>'SiCepat',
                        'pos'=>'Pos','tiki'=>'TIKI','anteraja'=>'AnterAja','ninja'=>'Ninja'
                    ];
                    $track_url  = ($exp_urls[$order['expedition']] ?? '#') . $order['tracking_number'];
                    $track_name = $exp_names[$order['expedition']] ?? strtoupper($order['expedition'] ?? '');
                ?>
                <a href="<?= $track_url ?>" target="_blank"
                   style="padding:6px 14px;background:rgba(139,92,246,.1);color:#a78bfa;border:1px solid rgba(139,92,246,.2);border-radius:7px;text-decoration:none;font-size:.78rem;font-weight:500;display:inline-flex;align-items:center;gap:5px">
                    <i class="bi bi-truck"></i> Lacak (<?= $track_name ?>)
                </a>
                <?php endif; ?>

                <?php if (in_array($order['status'], ['processing','shipped','delivered'])): ?>
                <a href="<?= BASE_URL ?>user/invoice/index.php?order_id=<?= $order['id'] ?>"
                   style="padding:6px 14px;background:rgba(16,185,129,.08);color:#34d399;border:1px solid rgba(16,185,129,.2);border-radius:7px;text-decoration:none;font-size:.78rem;font-weight:500;display:inline-flex;align-items:center;gap:5px">
                    <i class="bi bi-receipt"></i> Invoice
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($order['status'] !== 'cancelled'): ?>
        <?php
            $steps = ['pending','processing','shipped','delivered'];
            $cur   = array_search($order['status'], $steps);
        ?>
        <div style="padding:0 20px <?= in_array($order['status'], ['shipped','delivered']) && !empty($order['tracking_number']) ? '8px' : '14px' ?>">
            <div style="display:flex;align-items:center;gap:0">
                <?php foreach ($steps as $i => $step): ?>
                <div style="flex:1;display:flex;flex-direction:column;align-items:center">
                    <div style="width:20px;height:20px;border-radius:50%;background:<?= $i <= $cur ? 'var(--accent)' : 'var(--bg-base)' ?>;border:2px solid <?= $i <= $cur ? 'var(--accent)' : 'var(--border)' ?>;display:flex;align-items:center;justify-content:center;font-size:.6rem;color:#fff;z-index:1">
                        <?php if ($i < $cur): ?>
                        <i class="bi bi-check"></i>
                        <?php elseif ($i === $cur): ?>
                        <i class="bi bi-circle-fill" style="font-size:.4rem"></i>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:.62rem;color:<?= $i <= $cur ? 'var(--accent)' : 'var(--text-muted)' ?>;margin-top:4px;text-align:center">
                        <?= ['Menunggu','Diproses','Dikirim','Terkirim'][$i] ?>
                    </div>
                </div>
                <?php if ($i < count($steps) - 1): ?>
                <div style="flex:2;height:2px;background:<?= $i < $cur ? 'var(--accent)' : 'var(--border)' ?>;margin-bottom:18px"></div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Info resi saat shipped/delivered -->
        <?php if (in_array($order['status'], ['shipped','delivered']) && !empty($order['tracking_number'])): ?>
        <?php
            $exp_urls2  = ['jne'=>'https://www.jne.co.id/id/tracking/trace/','jnt'=>'https://www.jet.co.id/track/','sicepat'=>'https://www.sicepat.com/checkAwb/','pos'=>'https://www.posindonesia.co.id/id/tracking/','tiki'=>'https://tiki.id/id/tracking?awb=','anteraja'=>'https://anteraja.id/tracking/','ninja'=>'https://www.ninjaxpress.co/id-id/tracking?id='];
            $exp_names2 = ['jne'=>'JNE','jnt'=>'J&T Express','sicepat'=>'SiCepat','pos'=>'Pos Indonesia','tiki'=>'TIKI','anteraja'=>'AnterAja','ninja'=>'Ninja Express'];
            $t_url  = ($exp_urls2[$order['expedition']] ?? '#') . $order['tracking_number'];
            $t_name = $exp_names2[$order['expedition']] ?? strtoupper($order['expedition'] ?? '');
        ?>
        <div style="margin:0 20px 14px;background:rgba(59,130,246,.06);border:1px solid rgba(59,130,246,.15);border-radius:8px;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
            <div style="display:flex;align-items:center;gap:10px">
                <i class="bi bi-truck" style="color:var(--accent)"></i>
                <div>
                    <div style="font-size:.72rem;color:var(--text-muted)">Dikirim via <?= $t_name ?></div>
                    <div style="font-size:.82rem;font-weight:600;color:var(--text-primary);font-family:monospace"><?= htmlspecialchars($order['tracking_number']) ?></div>
                </div>
            </div>
            <a href="<?= $t_url ?>" target="_blank"
               style="padding:6px 14px;background:var(--accent);color:#fff;border-radius:7px;text-decoration:none;font-size:.75rem;font-weight:500;white-space:nowrap">
                <i class="bi bi-box-arrow-up-right"></i> Lacak Paket
            </a>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <!-- Label cancelled -->
        <div style="padding:0 20px 14px">
            <div style="display:inline-flex;align-items:center;gap:6px;font-size:.72rem;color:#f87171;background:rgba(239,68,68,.07);padding:5px 12px;border-radius:999px">
                <i class="bi bi-x-circle"></i> Pesanan ini telah dibatalkan
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>

<!-- Modal Konfirmasi Cancel -->
<div id="cancelModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999;align-items:center;justify-content:center;padding:16px">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;padding:28px;width:100%;max-width:380px">
        <div style="text-align:center;margin-bottom:20px">
            <div style="width:52px;height:52px;border-radius:50%;background:rgba(239,68,68,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 14px">
                <i class="bi bi-x-circle" style="font-size:1.5rem;color:#f87171"></i>
            </div>
            <h3 style="font-family:'Lora',serif;font-size:1rem;font-weight:600;margin-bottom:8px">Batalkan Pesanan?</h3>
            <p style="font-size:.82rem;color:var(--text-secondary);line-height:1.6">
                Pesanan <strong id="cancelOrderLabel"></strong> akan dibatalkan dan stok buku dikembalikan. Tindakan ini tidak bisa diurungkan.
            </p>
        </div>
        <form method="POST" action="<?= BASE_URL ?>user/orders/cancel.php" id="cancelForm">
            <input type="hidden" name="order_id" id="cancelOrderId">
            <div style="display:flex;gap:8px">
                <button type="submit"
                        style="flex:1;padding:10px;background:#ef4444;color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer">
                    Ya, Batalkan
                </button>
                <button type="button" onclick="closeCancel()"
                        style="flex:1;padding:10px;background:var(--bg-base);color:var(--text-secondary);border:1px solid var(--border);border-radius:8px;font-size:.85rem;cursor:pointer">
                    Tidak
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$extra_js = '<script>
function confirmCancel(id) {
    document.getElementById("cancelOrderId").value    = id;
    document.getElementById("cancelOrderLabel").textContent = "#" + id;
    document.getElementById("cancelModal").style.display = "flex";
}
function closeCancel() {
    document.getElementById("cancelModal").style.display = "none";
}
</script>';
?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>