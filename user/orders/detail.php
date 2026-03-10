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
        <!-- Tracking -->
        <?php if (in_array($order['status'], ['shipped','delivered']) && !empty($order['tracking_number'])): ?>
        <?php
            $exp_urls  = ['jne'=>'https://www.jne.co.id/id/tracking/trace/','jnt'=>'https://www.jet.co.id/track/','sicepat'=>'https://www.sicepat.com/checkAwb/','pos'=>'https://www.posindonesia.co.id/id/tracking/','tiki'=>'https://tiki.id/id/tracking?awb=','anteraja'=>'https://anteraja.id/tracking/','ninja'=>'https://www.ninjaxpress.co/id-id/tracking?id='];
            $exp_names = ['jne'=>'JNE','jnt'=>'J&T Express','sicepat'=>'SiCepat','pos'=>'Pos Indonesia','tiki'=>'TIKI','anteraja'=>'AnterAja','ninja'=>'Ninja Express'];
            $exp_logos = ['jne'=>'🟥','jnt'=>'🟧','sicepat'=>'🟦','pos'=>'🟩','tiki'=>'🟫','anteraja'=>'🟪','ninja'=>'⬛'];
            $t_url     = ($exp_urls[$order['expedition']] ?? '#') . strtoupper($order['tracking_number']);
            $t_name    = $exp_names[$order['expedition']] ?? strtoupper($order['expedition'] ?? '');
            $t_logo    = $exp_logos[$order['expedition']] ?? '📦';

            // Simulasi timeline lacak paket (data bodong)
            $fake_events = [];
            $base_time   = strtotime($order['created_at']);
            if ($order['status'] === 'shipped' || $order['status'] === 'delivered') {
                $fake_events[] = ['time' => date('d M Y, H:i', $base_time + 3600),        'desc' => 'Pesanan diambil oleh kurir ' . $t_name,         'loc' => 'Gudang ' . $t_name . ' Pusat'];
                $fake_events[] = ['time' => date('d M Y, H:i', $base_time + 7200),        'desc' => 'Paket tiba di hub sortir',                        'loc' => $t_name . ' Sorting Center'];
                $fake_events[] = ['time' => date('d M Y, H:i', $base_time + 18000),       'desc' => 'Paket dalam perjalanan ke kota tujuan',           'loc' => $t_name . ' Transit Hub'];
            }
            if ($order['status'] === 'delivered') {
                $fake_events[] = ['time' => date('d M Y, H:i', $base_time + 72000),       'desc' => 'Paket tiba di cabang tujuan',                     'loc' => $t_name . ' Cabang Lokal'];
                $fake_events[] = ['time' => date('d M Y, H:i', $base_time + 86400),       'desc' => 'Paket sedang diantar oleh kurir',                 'loc' => 'Dalam Pengiriman'];
                $fake_events[] = ['time' => date('d M Y, H:i', $base_time + 90000),       'desc' => 'Paket telah diterima',                            'loc' => 'Alamat Tujuan'];
            }
            $fake_events = array_reverse($fake_events);
        ?>
        <div style="background:var(--bg-card);border:1px solid rgba(59,130,246,.25);border-radius:12px;overflow:hidden">
            <!-- Header kartu -->
            <div style="padding:14px 18px;background:rgba(59,130,246,.07);border-bottom:1px solid rgba(59,130,246,.15);display:flex;align-items:center;justify-content:space-between">
                <div style="display:flex;align-items:center;gap:8px">
                    <i class="bi bi-truck" style="color:var(--accent);font-size:1rem"></i>
                    <span style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;color:var(--accent);font-weight:600">Lacak Paket</span>
                </div>
                <span style="font-size:.7rem;color:var(--text-muted);background:var(--bg-base);padding:2px 8px;border-radius:999px;border:1px solid var(--border)"><?= $t_name ?></span>
            </div>

            <div style="padding:14px 18px">
                <!-- Nomor resi -->
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;padding:10px 12px;background:var(--bg-base);border:1px solid var(--border);border-radius:8px">
                    <div>
                        <div style="font-size:.68rem;color:var(--text-muted);margin-bottom:2px">No. Resi</div>
                        <div style="font-family:monospace;font-size:.88rem;font-weight:700;color:var(--text-primary);letter-spacing:.05em"><?= htmlspecialchars(strtoupper($order['tracking_number'])) ?></div>
                    </div>
                    <button onclick="copyResi('<?= htmlspecialchars(strtoupper($order['tracking_number'])) ?>')"
                            id="copyBtn"
                            style="padding:5px 10px;background:var(--accent-soft);color:var(--accent);border:none;border-radius:6px;font-size:.72rem;cursor:pointer;display:flex;align-items:center;gap:4px">
                        <i class="bi bi-copy" id="copyIcon"></i> <span id="copyText">Salin</span>
                    </button>
                </div>

                <!-- Timeline bodong -->
                <div style="margin-bottom:14px">
                    <div style="font-size:.7rem;color:var(--text-muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:.06em">Riwayat Pengiriman</div>
                    <div style="position:relative;padding-left:20px">
                        <!-- Garis vertikal -->
                        <div style="position:absolute;left:6px;top:8px;bottom:8px;width:1px;background:var(--border)"></div>
                        <?php foreach ($fake_events as $ei => $ev): ?>
                        <div style="position:relative;margin-bottom:<?= $ei < count($fake_events)-1 ? '14px' : '0' ?>">
                            <!-- Dot -->
                            <div style="position:absolute;left:-17px;top:4px;width:10px;height:10px;border-radius:50%;background:<?= $ei === 0 ? 'var(--accent)' : 'var(--bg-base)' ?>;border:2px solid <?= $ei === 0 ? 'var(--accent)' : 'var(--border)' ?>"></div>
                            <div style="font-size:.78rem;font-weight:<?= $ei === 0 ? '600' : '400' ?>;color:<?= $ei === 0 ? 'var(--text-primary)' : 'var(--text-secondary)' ?>"><?= $ev['desc'] ?></div>
                            <div style="font-size:.68rem;color:var(--text-muted);margin-top:1px"><?= $ev['loc'] ?> &middot; <?= $ev['time'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tombol cek di website ekspedisi -->
                <a href="<?= $t_url ?>" target="_blank"
                   style="display:flex;align-items:center;justify-content:center;gap:7px;padding:9px;background:var(--accent);color:#fff;border-radius:8px;text-decoration:none;font-size:.8rem;font-weight:600">
                    <i class="bi bi-box-arrow-up-right"></i> Cek di Website <?= $t_name ?>
                </a>
                <div style="text-align:center;margin-top:6px;font-size:.68rem;color:var(--text-muted)">
                    Riwayat di atas adalah simulasi. Klik tombol untuk tracking resmi.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Update -->
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:18px">
            <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:6px">Terakhir diperbarui</div>
            <div style="font-size:.83rem;color:var(--text-secondary)"><?= date('d M Y, H:i', strtotime($order['updated_at'] ?? $order['created_at'])) ?></div>
        </div>
    </div>
</div>

<?php
$extra_js = '<script>
function copyResi(text) {
    navigator.clipboard.writeText(text).then(() => {
        document.getElementById("copyIcon").className = "bi bi-check-lg";
        document.getElementById("copyText").textContent = "Tersalin!";
        setTimeout(() => {
            document.getElementById("copyIcon").className = "bi bi-copy";
            document.getElementById("copyText").textContent = "Salin";
        }, 2000);
    });
}
</script>';
?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>