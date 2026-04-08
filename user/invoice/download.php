<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth_check.php';
requireUser();

$user_id  = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$format   = isset($_GET['format']) ? strtolower($_GET['format']) : 'pdf';

if (!$order_id || !in_array($format, ['pdf', 'jpeg'])) {
    header('Location: ../orders/index.php');
    exit;
}

// Ambil data order
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

if (!$order || !in_array($order['status'], ['processing', 'shipped', 'delivered'])) {
    header('Location: ../orders/index.php');
    exit;
}

// Ambil item
$stmt2 = $conn->prepare("
    SELECT oi.*, b.title, b.author
    FROM order_items oi
    JOIN books b ON oi.book_id = b.id
    WHERE oi.order_id = ?
");
$stmt2->bind_param('i', $order_id);
$stmt2->execute();
$items = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

$invoice_no = '#' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
$status_labels = ['processing' => 'Diproses', 'shipped' => 'Dikirim', 'delivered' => 'Selesai'];
$status_label  = $status_labels[$order['status']] ?? ucfirst($order['status']);

// ─────────────────────────────────────────────
// Build HTML invoice — dompdf-safe
// RULES: no flex/grid, no emoji, full table layout
// ─────────────────────────────────────────────
function buildInvoiceHtml($order, $items, $invoice_no, $status_label) {

    $rows = '';
    foreach ($items as $i => $item) {
        $bg       = $i % 2 === 0 ? '#ffffff' : '#f5f8ff';
        $subtotal = number_format($item['price'] * $item['quantity'], 0, ',', '.');
        $price    = number_format($item['price'], 0, ',', '.');
        $rows .= "
        <tr style='background:{$bg};'>
          <td style='padding:11px 14px; font-size:12px; border-bottom:1px solid #eef2f7;'>
            <strong style='color:#111; font-size:13px;'>" . htmlspecialchars($item['title']) . "</strong><br/>
            <span style='color:#888; font-size:11px;'>" . htmlspecialchars($item['author']) . "</span>
          </td>
          <td style='padding:11px 14px; text-align:center; font-size:13px; color:#555; border-bottom:1px solid #eef2f7;'>{$item['quantity']}</td>
          <td style='padding:11px 14px; text-align:right; font-size:12px; color:#555; border-bottom:1px solid #eef2f7;'>Rp {$price}</td>
          <td style='padding:11px 14px; text-align:right; font-size:13px; font-weight:bold; color:#111; border-bottom:1px solid #eef2f7;'>Rp {$subtotal}</td>
        </tr>";
    }

    $total   = number_format($order['total_price'], 0, ',', '.');
    $date    = date('d F Y', strtotime($order['created_at']));
    $printed = date('d F Y H:i');

    $phone_row = $order['user_phone']
        ? "<tr><td style='font-size:12px; color:#555; padding-top:2px;'>" . htmlspecialchars($order['user_phone']) . "</td></tr>"
        : '';

    $expedition_rows = '';
    if (!empty($order['expedition']) && !empty($order['tracking_number'])) {
        $expedition_rows = "
        <tr><td style='padding-top:10px; font-size:10px; color:#888; font-weight:bold; text-transform:uppercase; letter-spacing:1px;'>Ekspedisi</td></tr>
        <tr><td style='font-size:12px; color:#555; padding-top:2px;'>" . strtoupper(htmlspecialchars($order['expedition'])) . "</td></tr>
        <tr><td style='font-size:11px; color:#555; font-family:monospace;'>" . htmlspecialchars($order['tracking_number']) . "</td></tr>";
    }

    return "<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<style>
  * { margin:0; padding:0; }
  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 13px;
    color: #111;
    background: #fff;
  }
  table { border-collapse: collapse; width: 100%; }
  .page { padding: 40px 48px; }
</style>
</head>
<body>
<div class='page'>

  <!-- ══ HEADER ══ -->
  <table style='margin-bottom:20px;'>
    <tr>
      <td style='vertical-align:top;'>
        <div style='font-size:24px; font-weight:900; color:#111; letter-spacing:-0.5px;'>Bucookie</div>
        <div style='font-size:11px; color:#666; margin-top:3px;'>Toko Buku Online Terpercaya</div>
      </td>
      <td style='text-align:right; vertical-align:top;'>
        <div style='font-size:26px; font-weight:900; color:#3b82f6; letter-spacing:2px;'>INVOICE</div>
        <div style='font-size:13px; color:#444; margin-top:4px; font-weight:bold;'>{$invoice_no}</div>
        <div style='font-size:11px; color:#888; margin-top:2px;'>{$date}</div>
      </td>
    </tr>
  </table>

  <!-- ══ GARIS BIRU ══ -->
  <table style='margin-bottom:22px;'>
    <tr>
      <td style='background:#3b82f6; height:3px; font-size:0; line-height:0;'>&nbsp;</td>
    </tr>
  </table>

  <!-- ══ INFO 3 KOLOM ══ -->
  <table style='margin-bottom:28px; border-collapse:collapse;'>
    <tr>
      <!-- Tagihan Kepada -->
      <td style='width:33%; vertical-align:top; padding-right:18px; border-right:1px solid #e5e7eb;'>
        <table>
          <tr><td style='font-size:10px; font-weight:bold; color:#888; text-transform:uppercase; letter-spacing:1px; padding-bottom:8px;'>Tagihan Kepada</td></tr>
          <tr><td style='font-size:14px; font-weight:bold; color:#111;'>" . htmlspecialchars($order['user_name']) . "</td></tr>
          <tr><td style='font-size:12px; color:#555; padding-top:4px;'>" . htmlspecialchars($order['user_email']) . "</td></tr>
          {$phone_row}
        </table>
      </td>

      <!-- Alamat Pengiriman -->
      <td style='width:34%; vertical-align:top; padding:0 18px; border-right:1px solid #e5e7eb;'>
        <table>
          <tr><td style='font-size:10px; font-weight:bold; color:#888; text-transform:uppercase; letter-spacing:1px; padding-bottom:8px;'>Alamat Pengiriman</td></tr>
          <tr><td style='font-size:12px; color:#555; line-height:1.7;'>" . nl2br(htmlspecialchars($order['shipping_address'])) . "</td></tr>
        </table>
      </td>

      <!-- Status & Metode -->
      <td style='width:33%; vertical-align:top; padding-left:18px; text-align:right;'>
        <table style='width:100%;'>
          <tr><td style='font-size:10px; font-weight:bold; color:#888; text-transform:uppercase; letter-spacing:1px; padding-bottom:6px;'>Status Pesanan</td></tr>
          <tr><td>
            <span style='background:#dbeafe; color:#1e40af; padding:3px 12px; font-size:11px; font-weight:bold;'>{$status_label}</span>
          </td></tr>
          <tr><td style='padding-top:10px; font-size:10px; color:#888; font-weight:bold; text-transform:uppercase; letter-spacing:1px;'>Metode Bayar</td></tr>
          <tr><td style='font-size:12px; color:#555; padding-top:2px;'>Bayar di Tempat (COD)</td></tr>
          {$expedition_rows}
        </table>
      </td>
    </tr>
  </table>

  <!-- ══ TABEL ITEM ══ -->
  <table style='margin-bottom:24px; border-collapse:collapse;'>
    <thead>
      <tr style='background:#3b82f6;'>
        <th style='padding:10px 14px; text-align:left; font-size:11px; color:#fff; text-transform:uppercase; letter-spacing:0.5px; width:55%;'>Buku</th>
        <th style='padding:10px 14px; text-align:center; font-size:11px; color:#fff; text-transform:uppercase; width:10%;'>Qty</th>
        <th style='padding:10px 14px; text-align:right; font-size:11px; color:#fff; text-transform:uppercase; width:17%;'>Harga</th>
        <th style='padding:10px 14px; text-align:right; font-size:11px; color:#fff; text-transform:uppercase; width:18%;'>Subtotal</th>
      </tr>
    </thead>
    <tbody>
      {$rows}
    </tbody>
  </table>

  <!-- ══ TOTAL ══ -->
  <table style='border-collapse:collapse; margin-bottom:32px;'>
    <tr>
      <td style='width:60%;'>&nbsp;</td>
      <td style='width:40%;'>
        <table style='width:100%; border-collapse:collapse;'>
          <tr>
            <td style='padding:7px 0; font-size:12px; color:#555; border-bottom:1px solid #eee;'>Subtotal</td>
            <td style='padding:7px 0; font-size:12px; color:#555; text-align:right; border-bottom:1px solid #eee;'>Rp {$total}</td>
          </tr>
          <tr>
            <td style='padding:7px 0; font-size:12px; color:#555; border-bottom:1px solid #eee;'>Ongkos Kirim</td>
            <td style='padding:7px 0; font-size:12px; color:#16a34a; text-align:right; border-bottom:1px solid #eee; font-weight:bold;'>Gratis</td>
          </tr>
          <tr>
            <td style='padding:12px 0 6px; font-size:15px; font-weight:900; color:#111; border-top:2px solid #3b82f6;'>TOTAL</td>
            <td style='padding:12px 0 6px; font-size:16px; font-weight:900; color:#3b82f6; text-align:right; border-top:2px solid #3b82f6;'>Rp {$total}</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- ══ FOOTER ══ -->
  <table style='border-collapse:collapse; margin-bottom:0;'>
    <tr>
      <td style='border-top:1px solid #eee; padding-top:16px; text-align:center; color:#888; font-size:11px; line-height:2;'>
        Terima kasih telah berbelanja di <strong style='color:#3b82f6;'>Bucookie</strong>!<br/>
        Invoice dibuat otomatis pada {$printed} WIB<br/>
        <span style='font-size:10px; color:#aaa;'>Bucookie &mdash; Toko Buku Online &bull; admin@bucookie.com</span>
      </td>
    </tr>
  </table>

</div>
</body>
</html>";
}

$html = buildInvoiceHtml($order, $items, $invoice_no, $status_label);
$filename_base = 'Invoice_Bucookie_' . str_pad($order_id, 5, '0', STR_PAD_LEFT);

// ─────────────────────────────────────────────
// FORMAT: PDF via dompdf
// ─────────────────────────────────────────────
if ($format === 'pdf') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bucookie/vendor/autoload.php';

    $options = new \Dompdf\Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename_base . '.pdf"');
    header('Cache-Control: no-cache');
    echo $dompdf->output();
    exit;
}

// ─────────────────────────────────────────────
// FORMAT: JPEG via wkhtmltoimage atau GD fallback
// ─────────────────────────────────────────────
if ($format === 'jpeg') {
    // Simpan HTML sementara
    $tmp_html = sys_get_temp_dir() . '/invoice_' . $order_id . '_' . time() . '.html';
    $tmp_img  = sys_get_temp_dir() . '/invoice_' . $order_id . '_' . time() . '.jpg';
    file_put_contents($tmp_html, $html);

    // Coba wkhtmltoimage jika tersedia
    $wk = trim(shell_exec('which wkhtmltoimage 2>/dev/null'));
    if ($wk) {
        $cmd = escapeshellcmd($wk)
             . ' --quality 95 --width 794'
             . ' --enable-local-file-access'
             . ' ' . escapeshellarg($tmp_html)
             . ' ' . escapeshellarg($tmp_img)
             . ' 2>/dev/null';
        shell_exec($cmd);
        if (file_exists($tmp_img) && filesize($tmp_img) > 0) {
            header('Content-Type: image/jpeg');
            header('Content-Disposition: attachment; filename="' . $filename_base . '.jpg"');
            header('Cache-Control: no-cache');
            readfile($tmp_img);
            unlink($tmp_html);
            unlink($tmp_img);
            exit;
        }
    }

    // Fallback: GD — buat gambar invoice sederhana tapi rapi
    unlink($tmp_html);

    $W = 794;
    $padding = 52;

    // Hitung tinggi dinamis
    $row_h    = 40;
    $n_items  = count($items);
    $est_h    = 680 + ($n_items * $row_h);

    $img = imagecreatetruecolor($W, $est_h);

    // Warna
    $white     = imagecolorallocate($img, 255, 255, 255);
    $blue      = imagecolorallocate($img, 59, 130, 246);
    $blue_lt   = imagecolorallocate($img, 219, 234, 254);
    $dark      = imagecolorallocate($img, 17, 17, 17);
    $gray      = imagecolorallocate($img, 85, 85, 85);
    $light_gray= imagecolorallocate($img, 249, 250, 251);
    $line_gray = imagecolorallocate($img, 238, 238, 238);
    $muted     = imagecolorallocate($img, 136, 136, 136);
    $green_c   = imagecolorallocate($img, 22, 163, 74);

    imagefill($img, 0, 0, $white);

    $y = 48;

    // ── Nama brand
    imagestring($img, 5, $padding, $y, 'Bucookie - Toko Buku Online', $blue);
    imagestring($img, 2, $padding, $y + 22, 'Toko Buku Online Terpercaya', $muted);

    // ── INVOICE + nomor (kanan)
    imagestring($img, 5, $W - $padding - 160, $y, 'INVOICE', $blue);
    imagestring($img, 3, $W - $padding - 160, $y + 22, $invoice_no, $gray);
    imagestring($img, 2, $W - $padding - 160, $y + 40, date('d F Y', strtotime($order['created_at'])), $muted);

    $y += 80;

    // ── Garis biru
    imagefilledrectangle($img, $padding, $y, $W - $padding, $y + 3, $blue);
    $y += 18;

    // ── Info baris: Pembeli | Alamat | Status
    imagestring($img, 2, $padding, $y, 'TAGIHAN KEPADA:', $muted);
    imagestring($img, 3, $padding, $y + 16, $order['user_name'], $dark);
    imagestring($img, 2, $padding, $y + 34, $order['user_email'], $gray);
    if ($order['user_phone']) {
        imagestring($img, 2, $padding, $y + 50, $order['user_phone'], $gray);
    }

    $col2 = $padding + 250;
    imagestring($img, 2, $col2, $y, 'ALAMAT PENGIRIMAN:', $muted);
    $addr_lines = explode("\n", wordwrap($order['shipping_address'], 30, "\n", true));
    foreach (array_slice($addr_lines, 0, 3) as $k => $line) {
        imagestring($img, 2, $col2, $y + 16 + ($k * 16), trim($line), $gray);
    }

    $col3 = $padding + 530;
    imagestring($img, 2, $col3, $y, 'STATUS:', $muted);
    imagestring($img, 3, $col3, $y + 16, $status_label, $blue);
    imagestring($img, 2, $col3, $y + 36, 'Bayar di Tempat (COD)', $gray);
    if ($order['expedition']) {
        imagestring($img, 2, $col3, $y + 54, strtoupper($order['expedition']), $gray);
        imagestring($img, 2, $col3, $y + 70, $order['tracking_number'], $gray);
    }

    $y += 110;

    // ── Garis separator
    imageline($img, $padding, $y, $W - $padding, $y, $line_gray);
    $y += 14;

    // ── Header tabel
    imagefilledrectangle($img, $padding, $y, $W - $padding, $y + 34, $blue);
    imagestring($img, 3, $padding + 10, $y + 10, 'BUKU', $white);
    imagestring($img, 3, $padding + 430, $y + 10, 'QTY', $white);
    imagestring($img, 3, $padding + 520, $y + 10, 'HARGA', $white);
    imagestring($img, 3, $padding + 640, $y + 10, 'SUBTOTAL', $white);
    $y += 34;

    // ── Baris item
    foreach ($items as $i => $item) {
        $bg = $i % 2 === 0 ? $white : $light_gray;
        imagefilledrectangle($img, $padding, $y, $W - $padding, $y + $row_h - 1, $bg);

        $title = mb_strlen($item['title']) > 42 ? mb_substr($item['title'], 0, 42) . '...' : $item['title'];
        imagestring($img, 3, $padding + 10, $y + 8, $title, $dark);
        imagestring($img, 2, $padding + 10, $y + 24, $item['author'], $muted);

        imagestring($img, 3, $padding + 438, $y + 12, (string)$item['quantity'], $gray);

        $price_str = 'Rp ' . number_format($item['price'], 0, ',', '.');
        imagestring($img, 2, $padding + 490, $y + 12, $price_str, $gray);

        $sub_str = 'Rp ' . number_format($item['price'] * $item['quantity'], 0, ',', '.');
        imagestring($img, 3, $W - $padding - strlen($sub_str) * 7 - 10, $y + 12, $sub_str, $dark);

        imageline($img, $padding, $y + $row_h - 1, $W - $padding, $y + $row_h - 1, $line_gray);
        $y += $row_h;
    }

    $y += 20;

    // ── Total box
    $total_str = 'Rp ' . number_format($order['total_price'], 0, ',', '.');
    imageline($img, $padding + 420, $y, $W - $padding, $y, $line_gray);
    imagestring($img, 3, $padding + 430, $y + 8, 'Subtotal:', $gray);
    imagestring($img, 3, $W - $padding - strlen($total_str) * 7 - 10, $y + 8, $total_str, $gray);
    $y += 30;
    imageline($img, $padding + 420, $y, $W - $padding, $y, $line_gray);
    imagestring($img, 3, $padding + 430, $y + 8, 'Ongkos Kirim:', $gray);
    imagestring($img, 3, $W - $padding - 55, $y + 8, 'Gratis', $green_c);
    $y += 30;
    imagefilledrectangle($img, $padding + 420, $y, $W - $padding, $y + 38, $blue_lt);
    imagestring($img, 5, $padding + 430, $y + 10, 'TOTAL:', $blue);
    imagestring($img, 5, $W - $padding - strlen($total_str) * 9 - 10, $y + 10, $total_str, $blue);
    $y += 56;

    // ── Footer
    imageline($img, $padding, $y, $W - $padding, $y, $line_gray);
    $y += 14;
    $footer1 = 'Terima kasih telah berbelanja di Bucookie!';
    imagestring($img, 2, ($W - strlen($footer1) * 6) / 2, $y, $footer1, $muted);
    $y += 16;
    $footer2 = 'Invoice dibuat otomatis pada ' . date('d F Y H:i') . ' WIB';
    imagestring($img, 1, ($W - strlen($footer2) * 6) / 2, $y, $footer2, $muted);

    // Crop gambar sesuai konten
    $final = imagecrop($img, ['x' => 0, 'y' => 0, 'width' => $W, 'height' => $y + 40]);
    if (!$final) $final = $img;

    header('Content-Type: image/jpeg');
    header('Content-Disposition: attachment; filename="' . $filename_base . '.jpg"');
    header('Cache-Control: no-cache');
    imagejpeg($final, null, 95);
    imagedestroy($img);
    exit;
}