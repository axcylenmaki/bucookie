<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth_check.php';
requireUser();

$user_id  = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    header('Location: ../orders/index.php');
    exit;
}

// Ambil data order (pastikan milik user ini)
$stmt = $conn->prepare("
    SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone, u.address as user_address
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: ../orders/index.php');
    exit;
}

// Hanya tampilkan invoice jika sudah bukan pending
$allowed_statuses = ['processing', 'shipped', 'delivered'];
if (!in_array($order['status'], $allowed_statuses)) {
    header('Location: ../orders/index.php?msg=invoice_not_ready');
    exit;
}

// Ambil item pesanan
$stmt2 = $conn->prepare("
    SELECT oi.*, b.title, b.author, b.cover
    FROM order_items oi
    JOIN books b ON oi.book_id = b.id
    WHERE oi.order_id = ?
");
$stmt2->bind_param('i', $order_id);
$stmt2->execute();
$items = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// Format status label
$status_labels = [
    'processing' => 'Diproses',
    'shipped'    => 'Dikirim',
    'delivered'  => 'Selesai',
];
$status_label = $status_labels[$order['status']] ?? ucfirst($order['status']);

$page_title = 'Invoice #' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
require_once '../../includes/header.php';
?>

<div class="container" style="max-width:860px; margin: 40px auto; padding: 0 20px;">

  <!-- Toolbar tombol -->
  <div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <a href="../orders/index.php" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left"></i> Kembali ke Pesanan
    </a>
    <div class="d-flex gap-2">
      <a href="download.php?order_id=<?= $order_id ?>&format=pdf" class="btn btn-danger btn-sm">
        <i class="bi bi-file-earmark-pdf"></i> Download PDF
      </a>
      <a href="download.php?order_id=<?= $order_id ?>&format=jpeg" class="btn btn-warning btn-sm text-dark">
        <i class="bi bi-file-earmark-image"></i> Download JPEG
      </a>
    </div>
  </div>

  <!-- Invoice Card -->
  <div id="invoice-content" style="
    background: #fff;
    color: #111;
    border-radius: 12px;
    padding: 48px 52px;
    box-shadow: 0 4px 32px rgba(0,0,0,0.18);
    font-family: 'Sora', sans-serif;
  ">

    <!-- Header Invoice -->
    <div class="d-flex justify-content-between align-items-start mb-5">
      <div>
        <h1 style="font-size:2rem; font-weight:800; color:#111; margin:0; font-family:'Lora',serif;">
          Bucookie
        </h1>
        <p style="color:#555; margin:4px 0 0; font-size:0.85rem;">Toko Buku Online Terpercaya</p>
      </div>
      <div style="text-align:right;">
        <div style="font-size:1.5rem; font-weight:700; color:#3b82f6; letter-spacing:-0.5px;">INVOICE</div>
        <div style="font-size:0.9rem; color:#555; margin-top:4px;">
          #<?= str_pad($order_id, 5, '0', STR_PAD_LEFT) ?>
        </div>
        <div style="font-size:0.8rem; color:#888; margin-top:2px;">
          <?= date('d F Y', strtotime($order['created_at'])) ?>
        </div>
      </div>
    </div>

    <!-- Garis -->
    <hr style="border:2px solid #3b82f6; margin-bottom:32px;">

    <!-- Info Pembeli & Status -->
    <div class="row mb-5" style="display:flex; gap:0;">
      <div style="flex:1; padding-right:24px;">
        <div style="font-size:0.75rem; font-weight:700; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:8px;">Tagihan Kepada</div>
        <div style="font-weight:700; font-size:1rem; color:#111;"><?= htmlspecialchars($order['user_name']) ?></div>
        <div style="color:#555; font-size:0.85rem; margin-top:4px;"><?= htmlspecialchars($order['user_email']) ?></div>
        <?php if ($order['user_phone']): ?>
        <div style="color:#555; font-size:0.85rem;"><?= htmlspecialchars($order['user_phone']) ?></div>
        <?php endif; ?>
      </div>
      <div style="flex:1; padding-left:24px; border-left:1px solid #eee;">
        <div style="font-size:0.75rem; font-weight:700; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:8px;">Alamat Pengiriman</div>
        <div style="color:#555; font-size:0.85rem; line-height:1.6;">
          <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
        </div>
      </div>
      <div style="flex:1; padding-left:24px; border-left:1px solid #eee; text-align:right;">
        <div style="font-size:0.75rem; font-weight:700; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:8px;">Status Pesanan</div>
        <span style="
          display:inline-block;
          padding:4px 14px;
          border-radius:20px;
          font-size:0.82rem;
          font-weight:700;
          background:<?= $order['status'] === 'delivered' ? '#d1fae5' : ($order['status'] === 'shipped' ? '#dbeafe' : '#fef3c7') ?>;
          color:<?= $order['status'] === 'delivered' ? '#065f46' : ($order['status'] === 'shipped' ? '#1e40af' : '#92400e') ?>;
        "><?= $status_label ?></span>

        <div style="margin-top:12px; font-size:0.75rem; color:#888; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Metode Bayar</div>
        <div style="font-size:0.85rem; color:#555; margin-top:4px;">Bayar di Tempat (COD)</div>

        <?php if ($order['expedition'] && $order['tracking_number']): ?>
        <div style="margin-top:12px; font-size:0.75rem; color:#888; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Ekspedisi</div>
        <div style="font-size:0.85rem; color:#555; margin-top:4px;">
          <?= strtoupper(htmlspecialchars($order['expedition'])) ?><br>
          <span style="font-family:monospace; font-size:0.8rem;"><?= htmlspecialchars($order['tracking_number']) ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tabel Item -->
    <table style="width:100%; border-collapse:collapse; margin-bottom:32px;">
      <thead>
        <tr style="background:#3b82f6; color:#fff;">
          <th style="padding:12px 16px; text-align:left; font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; border-radius:6px 0 0 6px;">Buku</th>
          <th style="padding:12px 16px; text-align:center; font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">Qty</th>
          <th style="padding:12px 16px; text-align:right; font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">Harga Satuan</th>
          <th style="padding:12px 16px; text-align:right; font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; border-radius:0 6px 6px 0;">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i => $item): ?>
        <tr style="border-bottom:1px solid #f0f0f0; background:<?= $i % 2 === 0 ? '#fff' : '#f9fafb' ?>;">
          <td style="padding:14px 16px;">
            <div style="font-weight:600; font-size:0.9rem; color:#111;"><?= htmlspecialchars($item['title']) ?></div>
            <div style="font-size:0.78rem; color:#888; margin-top:2px;"><?= htmlspecialchars($item['author']) ?></div>
          </td>
          <td style="padding:14px 16px; text-align:center; font-size:0.9rem; color:#555;"><?= $item['quantity'] ?></td>
          <td style="padding:14px 16px; text-align:right; font-size:0.9rem; color:#555;">Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
          <td style="padding:14px 16px; text-align:right; font-weight:600; font-size:0.9rem; color:#111;">Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Total -->
    <div style="display:flex; justify-content:flex-end; margin-bottom:40px;">
      <div style="min-width:280px;">
        <div style="display:flex; justify-content:space-between; padding:8px 0; font-size:0.9rem; color:#555; border-bottom:1px solid #eee;">
          <span>Subtotal</span>
          <span>Rp <?= number_format($order['total_price'], 0, ',', '.') ?></span>
        </div>
        <div style="display:flex; justify-content:space-between; padding:8px 0; font-size:0.9rem; color:#555; border-bottom:1px solid #eee;">
          <span>Ongkos Kirim</span>
          <span style="color:#16a34a;">Gratis</span>
        </div>
        <div style="display:flex; justify-content:space-between; padding:14px 0 8px; font-size:1.1rem; font-weight:800; color:#111; border-top:2px solid #3b82f6; margin-top:4px;">
          <span>Total</span>
          <span style="color:#3b82f6;">Rp <?= number_format($order['total_price'], 0, ',', '.') ?></span>
        </div>
      </div>
    </div>

    <!-- Footer Invoice -->
    <hr style="border:1px solid #eee; margin-bottom:20px;">
    <div style="text-align:center; color:#888; font-size:0.8rem; line-height:1.8;">
      <p style="margin:0;">Terima kasih telah berbelanja di <strong style="color:#3b82f6;">Bucookie</strong>!</p>
      <p style="margin:0;">Invoice ini dibuat secara otomatis pada <?= date('d F Y H:i') ?> WIB</p>
      <p style="margin:0; margin-top:6px; font-size:0.72rem; color:#aaa;">
        Bucookie — Toko Buku Online &bull; bucookie.id &bull; admin@bucookie.com
      </p>
    </div>

  </div><!-- end invoice-content -->

</div>

<style>
@media print {
  .no-print { display: none !important; }
  body { background: white !important; }
}
</style>

<?php require_once '../../includes/footer.php'; ?>