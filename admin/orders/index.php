<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

$msg    = $_GET['msg']    ?? '';
$status = $_GET['status'] ?? '';

$page_title = 'Pesanan';
require_once __DIR__ . '/../../admin/includes/header.php';

$allowed_statuses = ['pending','processing','shipped','delivered','cancelled'];
$where = '';
if ($status && in_array($status, $allowed_statuses)) {
    $s     = $conn->real_escape_string($status);
    $where = "WHERE o.status = '$s'";
}

// 1. Ambil Data Pesanan untuk Tabel
$orders_query = $conn->query("
    SELECT o.*, u.name AS user_name, u.email AS user_email,
           COUNT(oi.id) AS total_items
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    $where
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

// 2. Ambil Item Buku untuk Detail (Map)
$all_items_raw = $conn->query("
    SELECT oi.order_id, oi.quantity, oi.price, b.title, b.author, b.cover
    FROM order_items oi
    JOIN books b ON oi.book_id = b.id
");
$order_items_map = [];
while ($row = $all_items_raw->fetch_assoc()) {
    $order_items_map[$row['order_id']][] = $row;
}

// 3. Siapkan Data JSON untuk Javascript (PENTING: Ditaruh di atas agar siap dipakai)
$orders_json = [];
$orders_for_js = $conn->query("
    SELECT o.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
");
while ($o = $orders_for_js->fetch_assoc()) {
    $orders_json[$o['id']] = [
        'id'               => $o['id'],
        'user_name'        => $o['user_name'],
        'user_email'       => $o['user_email'],
        'user_phone'       => $o['user_phone'] ?? '-',
        'total_price'      => $o['total_price'],
        'shipping_address' => $o['shipping_address'],
        'status'           => $o['status'],
        'expedition'       => $o['expedition'] ?? '',
        'tracking_number'  => $o['tracking_number'] ?? '',
        'created_at'       => date('d M Y, H:i', strtotime($o['created_at'])),
        'items'            => $order_items_map[$o['id']] ?? [],
    ];
}

$status_label = [
    'pending'    => ['label'=>'Menunggu',   'color'=>'#fbbf24'],
    'processing' => ['label'=>'Diproses',   'color'=>'#60a5fa'],
    'shipped'    => ['label'=>'Dikirim',    'color'=>'#a78bfa'],
    'delivered'  => ['label'=>'Terkirim',   'color'=>'#4ade80'],
    'cancelled'  => ['label'=>'Dibatalkan', 'color'=>'#f87171'],
];
?>

<div class="page-header">
    <h1>Pesanan</h1>
    <p>Monitor dan kelola semua pesanan dari user</p>
</div>

<?php if ($msg === 'updated'): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> Status pesanan berhasil diperbarui.</div>
<?php elseif ($msg === 'invalid_flow'): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Gagal: Status tidak boleh diturunkan atau sudah final.</div>
<?php endif; ?>

<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
    <a href="<?= BASE_URL ?>admin/orders/index.php" 
       style="padding:6px 16px;border-radius:999px;border:1px solid var(--border);background:<?= !$status ? 'var(--accent-soft)' : 'var(--bg-card)' ?>;color:<?= !$status ? 'var(--accent)' : 'var(--text-secondary)' ?>;font-size:.78rem;text-decoration:none">
       Semua
    </a>
    <?php foreach ($allowed_statuses as $s): ?>
    <a href="<?= BASE_URL ?>admin/orders/index.php?status=<?= $s ?>"
       style="padding:6px 16px;border-radius:999px;border:1px solid <?= $status===$s ? 'var(--accent)' : 'var(--border)' ?>;background:<?= $status===$s ? 'var(--accent-soft)' : 'var(--bg-card)' ?>;color:<?= $status===$s ? 'var(--accent)' : 'var(--text-secondary)' ?>;font-size:.78rem;text-decoration:none">
        <?= ucfirst($s) ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="table-card">
    <div class="table-card-header">
        <h2><i class="bi bi-bag-check me-2"></i>Daftar Pesanan</h2>
    </div>
    <div class="table-responsive">
        <?php if ($orders_query->num_rows === 0): ?>
            <div class="empty-state">
                <i class="bi bi-bag-x"></i>
                <p>Belum ada pesanan<?= $status ? " dengan status <b>$status</b>" : '' ?></p>
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#ID</th>
                    <th>User</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Alamat</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $orders_query->fetch_assoc()): 
                    $items = $order_items_map[$order['id']] ?? [];
                    $isLocked = in_array($order['status'], ['delivered', 'cancelled']);
                ?>
                <tr>
                    <td class="td-bold">#<?= $order['id'] ?></td>
                    <td>
                        <div style="font-size:.83rem;font-weight:500;color:var(--text-primary)"><?= htmlspecialchars($order['user_name']) ?></div>
                        <div style="font-size:.72rem;color:var(--text-muted)"><?= htmlspecialchars($order['user_email']) ?></div>
                    </td>
                    <td>
                        <button onclick="openDetail(<?= $order['id'] ?>)" style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;padding:0">
                            <span style="font-size:.75rem;color:var(--accent);text-decoration:underline dotted"><?= $order['total_items'] ?> item</span>
                        </button>
                    </td>
                    <td class="td-bold">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>
                    <td style="max-width:150px;font-size:.78rem"><?= htmlspecialchars($order['shipping_address']) ?></td>
                    <td><span class="badge-status badge-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                    <td style="font-size:.78rem"><?= date('d M Y H:i', strtotime($order['created_at'])) ?></td>
                    <td style="white-space:nowrap">
                        <button class="btn-action btn-view" onclick="openDetail(<?= $order['id'] ?>)">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn-action btn-edit ms-1" 
                                onclick="openModal(<?= $order['id'] ?>, '<?= $order['status'] ?>')"
                                <?= $isLocked ? 'disabled style="opacity:.35;cursor:not-allowed"' : '' ?>>
                            <i class="bi bi-pencil-square"></i> Status
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div id="detailModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:998;align-items:center;justify-content:center;padding:16px">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;width:100%;max-width:560px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden">
        <div style="padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
            <h3 style="font-size:1rem;font-weight:600" id="detailTitle">Detail Pesanan</h3>
            <button onclick="closeDetail()" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.1rem;"><i class="bi bi-x-lg"></i></button>
        </div>
        <div id="detailBody" style="overflow-y:auto;flex:1;padding:20px 22px"></div>
        <div style="padding:14px 22px;border-top:1px solid var(--border);display:flex;justify-content:flex-end">
            <button onclick="closeDetail()" class="btn-cancel">Tutup</button>
        </div>
    </div>
</div>

<div id="statusModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:999;align-items:center;justify-content:center">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;padding:28px;width:100%;max-width:420px;margin:16px">
        <h3 style="font-size:1.1rem;margin-bottom:16px">Update Status Pesanan</h3>
        <form method="POST" action="<?= BASE_URL ?>admin/orders/update_status.php">
            <input type="hidden" name="order_id" id="modalOrderId">
            <div class="mb-3">
                <label class="form-label">Status Baru</label>
                <select name="status" id="modalStatus" class="form-select" onchange="toggleTrackingFields()"></select>
            </div>

            <div id="trackingFields" style="display:none;border:1px solid var(--border);border-radius:8px;padding:14px;background:var(--bg-base);margin-bottom:14px">
                <label class="form-label">Ekspedisi</label>
                <select name="expedition" id="modalExpedition" class="form-select mb-2" onchange="updateTrackingLink()">
                    <option value="">-- Pilih Ekspedisi --</option>
                    <option value="jne">JNE</option>
                    <option value="jnt">J&T Express</option>
                    <option value="sicepat">SiCepat</option>
                </select>
                <label class="form-label">Nomor Resi</label>
                <input type="text" name="tracking_number" id="modalTracking" class="form-control" oninput="updateTrackingLink()" style="text-transform:uppercase">
            </div>

            <div style="display:flex;gap:8px">
                <button type="submit" class="btn-save">Simpan</button>
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<?php
// TATA LETAK JS SANGAT BERPENGARUH
$extra_js = '
<script>
    // Pastikan data PHP masuk ke JS sebelum file orders.js dipanggil
    const ORDERS = ' . json_encode($orders_json) . ';
    const BASE_URL = "' . BASE_URL . '";
    const STATUS_LABEL = ' . json_encode($status_label) . ';
</script>
<script src="' . BASE_URL . 'admin/orders/orders.js?v=' . time() . '"></script>
';

require_once __DIR__ . '/../../admin/includes/footer.php';
?>