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

$orders = $conn->query("
    SELECT o.*, u.name AS user_name, u.email AS user_email,
           COUNT(oi.id) AS total_items
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    $where
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

// Ambil semua order items sekaligus untuk modal detail
$all_items_raw = $conn->query("
    SELECT oi.order_id, oi.quantity, oi.price, b.title, b.author, b.cover
    FROM order_items oi
    JOIN books b ON oi.book_id = b.id
");
$order_items_map = [];
while ($row = $all_items_raw->fetch_assoc()) {
    $order_items_map[$row['order_id']][] = $row;
}
?>

<div class="page-header">
    <h1>Pesanan</h1>
    <p>Monitor dan kelola semua pesanan dari user</p>
</div>

<?php if ($msg === 'updated'): ?>
<div class="alert alert-success"><i class="bi bi-check-circle"></i> Status pesanan berhasil diperbarui.</div>
<?php elseif ($msg === 'already_cancelled'): ?>
<div class="alert alert-danger"><i class="bi bi-slash-circle"></i> Pesanan ini sudah dibatalkan dan tidak bisa diubah statusnya.</div>
<?php endif; ?>

<!-- Filter Status -->
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
        <?php if ($orders->num_rows === 0): ?>
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
                <?php while ($order = $orders->fetch_assoc()):
                    $items = $order_items_map[$order['id']] ?? [];
                ?>
                <tr>
                    <td class="td-bold">#<?= $order['id'] ?></td>
                    <td>
                        <div style="font-size:.83rem;font-weight:500;color:var(--text-primary)"><?= htmlspecialchars($order['user_name']) ?></div>
                        <div style="font-size:.72rem;color:var(--text-muted)"><?= htmlspecialchars($order['user_email']) ?></div>
                    </td>
                    <td>
                        <!-- Preview buku mini, klik buka detail -->
                        <button onclick="openDetail(<?= $order['id'] ?>)"
                                style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;padding:0">
                            <div style="display:flex;gap:-4px">
                                <?php foreach (array_slice($items, 0, 3) as $item): ?>
                                <div style="width:28px;height:36px;border-radius:3px;overflow:hidden;border:1px solid var(--border);flex-shrink:0;background:var(--bg-base)">
                                    <?php if (!empty($item['cover']) && file_exists(__DIR__ . '/../../assets/uploads/covers/' . $item['cover'])): ?>
                                    <img src="<?= BASE_URL ?>assets/uploads/covers/<?= htmlspecialchars($item['cover']) ?>"
                                         style="width:100%;height:100%;object-fit:cover">
                                    <?php else: ?>
                                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center">
                                        <i class="bi bi-book" style="font-size:.5rem;color:var(--text-muted)"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                                <?php if (count($items) > 3): ?>
                                <div style="width:28px;height:36px;border-radius:3px;background:var(--accent-soft);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:.6rem;color:var(--accent);font-weight:700">
                                    +<?= count($items) - 3 ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <span style="font-size:.75rem;color:var(--accent);text-decoration:underline dotted"><?= $order['total_items'] ?> item</span>
                        </button>
                    </td>
                    <td class="td-bold">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></td>
                    <td style="max-width:150px;font-size:.78rem"><?= htmlspecialchars($order['shipping_address']) ?></td>
                    <td><span class="badge-status badge-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                    <td style="font-size:.78rem"><?= date('d M Y H:i', strtotime($order['created_at'])) ?></td>
                    <td style="white-space:nowrap">
                        <button class="btn-action btn-view"
                                onclick="openDetail(<?= $order['id'] ?>)">
                            <i class="bi bi-eye"></i> Detail
                        </button>
                        <?php if ($order['status'] === 'cancelled'): ?>
                        <button class="btn-action ms-1"
                                disabled
                                title="Pesanan sudah dibatalkan, tidak bisa diubah"
                                style="opacity:.35;cursor:not-allowed">
                            <i class="bi bi-slash-circle"></i> Status
                        </button>
                        <?php else: ?>
                        <button class="btn-action btn-edit ms-1"
                                onclick="openModal(<?= $order['id'] ?>, '<?= $order['status'] ?>')">
                            <i class="bi bi-pencil-square"></i> Status
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- ══ MODAL DETAIL PESANAN ══ -->
<div id="detailModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:998;align-items:center;justify-content:center;padding:16px">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;width:100%;max-width:560px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden">

        <!-- Header modal -->
        <div style="padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
            <h3 style="font-family:'Lora',serif;font-size:1rem;font-weight:600" id="detailTitle">Detail Pesanan</h3>
            <button onclick="closeDetail()" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.1rem;line-height:1">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Body modal (scrollable) -->
        <div id="detailBody" style="overflow-y:auto;flex:1;padding:20px 22px">
            <!-- diisi JS -->
        </div>

        <!-- Footer modal -->
        <div style="padding:14px 22px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;flex-shrink:0">
            <button onclick="closeDetail()" class="btn-cancel" style="margin:0">Tutup</button>
        </div>
    </div>
</div>

<!-- ══ MODAL UPDATE STATUS ══ -->
<div id="statusModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:999;align-items:center;justify-content:center">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;padding:28px;width:100%;max-width:400px;margin:16px">
        <h3 style="font-family:'Lora',serif;font-size:1.1rem;margin-bottom:16px">Update Status Pesanan</h3>
        <form method="POST" action="<?= BASE_URL ?>admin/orders/update_status.php">
            <input type="hidden" name="order_id" id="modalOrderId">
            <div class="mb-field">
                <label class="form-label">Status Baru</label>
                <select name="status" id="modalStatus" class="form-select">
                    <?php foreach ($allowed_statuses as $s): ?>
                    <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn-save"><i class="bi bi-check-lg"></i> Simpan</button>
                <button type="button" class="btn-cancel" onclick="closeModal()" style="margin:0">Batal</button>
            </div>
        </form>
    </div>
</div>

<?php
// Encode semua data pesanan ke JSON untuk dipakai JS
$orders_json = [];
$all_orders->num_rows ?? null;

// Re-query karena pointer sudah habis
$orders2 = $conn->query("
    SELECT o.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");
while ($o = $orders2->fetch_assoc()) {
    $orders_json[$o['id']] = [
        'id'               => $o['id'],
        'user_name'        => $o['user_name'],
        'user_email'       => $o['user_email'],
        'user_phone'       => $o['user_phone'] ?? '-',
        'total_price'      => $o['total_price'],
        'shipping_address' => $o['shipping_address'],
        'status'           => $o['status'],
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

$extra_js = '<script>
const ORDERS = ' . json_encode($orders_json) . ';
const BASE_URL = "' . BASE_URL . '";
const STATUS_LABEL = ' . json_encode($status_label) . ';

function formatRp(n) {
    return "Rp " + parseInt(n).toLocaleString("id-ID");
}

function openDetail(id) {
    const o = ORDERS[id];
    if (!o) return;

    document.getElementById("detailTitle").textContent = "Detail Pesanan #" + o.id;

    const sl = STATUS_LABEL[o.status] || {label: o.status, color: "#fff"};

    let itemsHtml = "";
    o.items.forEach(item => {
        const coverHtml = item.cover
            ? `<img src="${BASE_URL}assets/uploads/covers/${item.cover}" style="width:100%;height:100%;object-fit:cover">`
            : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center"><i class="bi bi-book" style="color:var(--text-muted);font-size:.7rem"></i></div>`;
        itemsHtml += `
        <div style="display:flex;gap:12px;align-items:center;padding:10px 0;border-bottom:1px solid var(--border)">
            <div style="width:40px;height:52px;border-radius:5px;overflow:hidden;flex-shrink:0;background:var(--bg-base);border:1px solid var(--border)">${coverHtml}</div>
            <div style="flex:1;min-width:0">
                <div style="font-size:.83rem;font-weight:500;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${item.title}</div>
                <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px">${item.author}</div>
            </div>
            <div style="text-align:right;flex-shrink:0">
                <div style="font-size:.75rem;color:var(--text-muted)">×${item.quantity}</div>
                <div style="font-size:.82rem;font-weight:600;color:var(--accent)">${formatRp(item.price * item.quantity)}</div>
            </div>
        </div>`;
    });

    document.getElementById("detailBody").innerHTML = `
        <!-- Info user -->
        <div style="background:var(--bg-base);border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:16px">
            <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:8px">Informasi Pembeli</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:.8rem">
                <div><div style="color:var(--text-muted);font-size:.7rem">Nama</div><div style="color:var(--text-primary);font-weight:500;margin-top:2px">${o.user_name}</div></div>
                <div><div style="color:var(--text-muted);font-size:.7rem">Email</div><div style="color:var(--text-primary);margin-top:2px">${o.user_email}</div></div>
                <div><div style="color:var(--text-muted);font-size:.7rem">No. HP</div><div style="color:var(--text-primary);margin-top:2px">${o.user_phone}</div></div>
                <div><div style="color:var(--text-muted);font-size:.7rem">Tanggal</div><div style="color:var(--text-primary);margin-top:2px">${o.created_at}</div></div>
            </div>
            <div style="margin-top:8px"><div style="color:var(--text-muted);font-size:.7rem">Alamat Pengiriman</div><div style="color:var(--text-primary);font-size:.8rem;margin-top:2px;line-height:1.5">${o.shipping_address}</div></div>
        </div>

        <!-- Status -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <span style="font-size:.78rem;color:var(--text-muted)">Status Pesanan</span>
            <span style="background:${sl.color}22;color:${sl.color};padding:4px 12px;border-radius:999px;font-size:.72rem;font-weight:600">${sl.label}</span>
        </div>

        <!-- Items -->
        <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:4px">Buku Dipesan</div>
        <div>${itemsHtml}</div>

        <!-- Total -->
        <div style="display:flex;justify-content:space-between;align-items:center;padding-top:12px;margin-top:4px">
            <span style="font-size:.83rem;color:var(--text-muted)">Total Pembayaran</span>
            <span style="font-size:1.05rem;font-weight:700;color:var(--accent)">${formatRp(o.total_price)}</span>
        </div>
        <div style="font-size:.72rem;color:var(--text-muted);text-align:right;margin-top:2px">
            <i class="bi bi-truck"></i> Payment at Delivery
        </div>
    `;

    document.getElementById("detailModal").style.display = "flex";
}

function closeDetail() {
    document.getElementById("detailModal").style.display = "none";
}

function openModal(id, status) {
    document.getElementById("modalOrderId").value = id;
    document.getElementById("modalStatus").value  = status;
    document.getElementById("statusModal").style.display = "flex";
}

function closeModal() {
    document.getElementById("statusModal").style.display = "none";
}
</script>';
?>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>