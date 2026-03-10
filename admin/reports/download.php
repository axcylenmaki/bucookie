<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

// Cek vendor dompdf
$autoload = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoload)) {
    die('<h3>Error: dompdf belum diinstall. Jalankan <code>composer require dompdf/dompdf</code> di folder bucookie.</h3>');
}
require_once $autoload;
use Dompdf\Dompdf;
use Dompdf\Options;

// Ambil parameter filter (sama dengan index.php)
$filter_type  = $_GET['filter_type']  ?? 'month';
$filter_year  = (int)($_GET['filter_year']  ?? date('Y'));
$filter_month = (int)($_GET['filter_month'] ?? date('n'));
$filter_day   = (int)($_GET['filter_day']   ?? date('j'));
$date_from    = $_GET['date_from'] ?? date('Y-m-01');
$date_to      = $_GET['date_to']   ?? date('Y-m-d');

$where_completed = "o.status IN ('delivered','shipped','processing')";
switch ($filter_type) {
    case 'day':
        $date_str = sprintf('%04d-%02d-%02d', $filter_year, $filter_month, $filter_day);
        $where_date = "AND DATE(o.created_at) = '$date_str'";
        $period_label = date('d F Y', strtotime($date_str));
        break;
    case 'year':
        $where_date = "AND YEAR(o.created_at) = $filter_year";
        $period_label = "Tahun $filter_year";
        break;
    case 'range':
        $df = $conn->real_escape_string($date_from);
        $dt = $conn->real_escape_string($date_to);
        $where_date = "AND DATE(o.created_at) BETWEEN '$df' AND '$dt'";
        $period_label = date('d M Y', strtotime($date_from)) . ' s/d ' . date('d M Y', strtotime($date_to));
        break;
    default:
        $where_date = "AND YEAR(o.created_at)=$filter_year AND MONTH(o.created_at)=$filter_month";
        $period_label = date('F Y', mktime(0,0,0,$filter_month,1,$filter_year));
}

$summary = $conn->query("
    SELECT COUNT(*) AS total_orders, SUM(o.total_price) AS total_revenue,
           COUNT(CASE WHEN o.status='pending' THEN 1 END) AS pending,
           COUNT(CASE WHEN o.status='cancelled' THEN 1 END) AS cancelled,
           COUNT(CASE WHEN o.status='delivered' THEN 1 END) AS delivered
    FROM orders o WHERE 1=1 $where_date
")->fetch_assoc();

$orders_q = $conn->query("
    SELECT o.id, u.name AS user_name, o.total_price, o.status, o.created_at, COUNT(oi.id) AS total_items
    FROM orders o JOIN users u ON o.user_id=u.id LEFT JOIN order_items oi ON oi.order_id=o.id
    WHERE 1=1 $where_date GROUP BY o.id ORDER BY o.created_at DESC
");
$orders_data = [];
while ($r = $orders_q->fetch_assoc()) $orders_data[] = $r;

// Buku terlaris
$top_q = $conn->query("
    SELECT b.title, b.author, SUM(oi.quantity) AS total_qty, SUM(oi.quantity*oi.price) AS total_rev
    FROM order_items oi JOIN books b ON oi.book_id=b.id JOIN orders o ON oi.order_id=o.id
    WHERE $where_completed $where_date GROUP BY b.id ORDER BY total_qty DESC LIMIT 5
");
$top_books = [];
while ($r = $top_q->fetch_assoc()) $top_books[] = $r;

// Data chart untuk gambar (base64 PNG via Chart.js SSR tidak tersedia,
// kita buat bar chart manual pakai SVG inline di HTML dompdf)
$chart_q = $conn->query("
    SELECT MONTH(o.created_at) AS m, SUM(o.total_price) AS rev, COUNT(*) AS cnt
    FROM orders o WHERE $where_completed $where_date GROUP BY MONTH(o.created_at) ORDER BY m
");
$chart_by_month = [];
for ($i=1;$i<=12;$i++) $chart_by_month[$i] = ['rev'=>0,'cnt'=>0];
while ($r=$chart_q->fetch_assoc()) { $chart_by_month[(int)$r['m']]['rev']=(float)$r['rev']; $chart_by_month[(int)$r['m']]['cnt']=(int)$r['cnt']; }

// Hanya tampilkan bulan yg ada data, atau 12 bulan untuk filter year
$bln_names=['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
if ($filter_type === 'year') {
    $chart_data = [];
    for ($i=1;$i<=12;$i++) $chart_data[] = ['label'=>$bln_names[$i],'rev'=>$chart_by_month[$i]['rev'],'cnt'=>$chart_by_month[$i]['cnt']];
} else {
    $chart_data = array_filter($chart_by_month, fn($v)=>$v['rev']>0 || $v['cnt']>0);
    $tmp = [];
    foreach ($chart_data as $m=>$v) $tmp[] = ['label'=>$bln_names[$m],'rev'=>$v['rev'],'cnt'=>$v['cnt']];
    $chart_data = $tmp ?: [['label'=>'(kosong)','rev'=>0,'cnt'=>0]];
}

// Build SVG bar chart
$max_rev = max(array_column($chart_data,'rev')) ?: 1;
$bar_count = count($chart_data);
$svg_w = 520; $svg_h = 180;
$pad_l = 60; $pad_r = 10; $pad_t = 15; $pad_b = 35;
$inner_w = $svg_w - $pad_l - $pad_r;
$inner_h = $svg_h - $pad_t - $pad_b;
$bar_gap = 4;
$bar_w   = max(6, floor($inner_w/$bar_count) - $bar_gap);

$svg = "<svg xmlns='http://www.w3.org/2000/svg' width='{$svg_w}' height='{$svg_h}'>";
$svg .= "<rect width='{$svg_w}' height='{$svg_h}' fill='#f9fafb' rx='6'/>";
// Grid lines
for ($g=0;$g<=4;$g++) {
    $y = $pad_t + $inner_h - ($g/4)*$inner_h;
    $svg .= "<line x1='{$pad_l}' y1='{$y}' x2='".($svg_w-$pad_r)."' y2='{$y}' stroke='#e5e7eb' stroke-width='1'/>";
    $lbl = 'Rp '.number_format(($max_rev*$g/4)/1000,0,',','.').'k';
    $svg .= "<text x='".($pad_l-4)."' y='".($y+4)."' font-size='8' fill='#9ca3af' text-anchor='end'>{$lbl}</text>";
}
foreach ($chart_data as $i => $d) {
    $bh   = $inner_h * ($d['rev']/$max_rev);
    $bx   = $pad_l + $i * ($bar_w+$bar_gap) + $bar_gap/2;
    $by   = $pad_t + $inner_h - $bh;
    $svg .= "<rect x='{$bx}' y='{$by}' width='{$bar_w}' height='{$bh}' fill='#3b82f6' rx='2'/>";
    $lx   = $bx + $bar_w/2;
    $ly   = $pad_t + $inner_h + 14;
    $svg .= "<text x='{$lx}' y='{$ly}' font-size='8' fill='#6b7280' text-anchor='middle'>{$d['label']}</text>";
}
$svg .= "</svg>";

// Status badge warna
$status_color = ['pending'=>'#f59e0b','processing'=>'#3b82f6','shipped'=>'#8b5cf6','delivered'=>'#22c55e','cancelled'=>'#ef4444'];
$status_label = ['pending'=>'Menunggu','processing'=>'Diproses','shipped'=>'Dikirim','delivered'=>'Terkirim','cancelled'=>'Dibatalkan'];

$rows_html = '';
foreach ($orders_data as $o) {
    $sc = $status_color[$o['status']] ?? '#ccc';
    $sl = $status_label[$o['status']] ?? $o['status'];
    $rows_html .= "
    <tr>
        <td>#{$o['id']}</td>
        <td>".htmlspecialchars($o['user_name'])."</td>
        <td style='text-align:center'>{$o['total_items']}</td>
        <td style='text-align:right'>Rp ".number_format($o['total_price'],0,',','.')."</td>
        <td style='text-align:center'><span style='background:{$sc}22;color:{$sc};padding:2px 8px;border-radius:999px;font-size:9px'>{$sl}</span></td>
        <td>".date('d M Y', strtotime($o['created_at']))."</td>
    </tr>";
}

$top_rows = '';
foreach ($top_books as $i => $bk) {
    $top_rows .= "<tr><td>".($i+1)."</td><td>".htmlspecialchars($bk['title'])."</td><td>".htmlspecialchars($bk['author'])."</td><td style='text-align:center'>{$bk['total_qty']}</td><td style='text-align:right'>Rp ".number_format($bk['total_rev'],0,',','.')."</td></tr>";
}

$html = '
<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 24px; }
    h1 { font-size: 20px; color: #111827; margin: 0 0 2px; }
    .subtitle { font-size: 11px; color: #6b7280; margin-bottom: 20px; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 2px solid #3b82f6; padding-bottom: 14px; }
    .brand { font-size: 22px; font-weight: bold; color: #3b82f6; }
    .brand span { color: #1f2937; }
    .stat-grid { display: table; width: 100%; margin-bottom: 20px; border-collapse: separate; border-spacing: 8px; }
    .stat-box { display: table-cell; background: #f3f4f6; border-radius: 8px; padding: 12px 16px; width: 20%; }
    .stat-num { font-size: 16px; font-weight: bold; color: #111827; }
    .stat-lbl { font-size: 9px; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; margin-top: 3px; }
    h2 { font-size: 13px; color: #111827; margin: 18px 0 10px; border-left: 3px solid #3b82f6; padding-left: 8px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    thead th { background: #f3f4f6; padding: 7px 10px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
    tbody td { padding: 7px 10px; border-bottom: 1px solid #f3f4f6; font-size: 10px; color: #374151; }
    tbody tr:nth-child(even) td { background: #f9fafb; }
    .footer { margin-top: 30px; font-size: 9px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    .chart-wrap { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-bottom: 20px; }
</style>
</head><body>

<div class="header">
    <div>
        <div class="brand">Bu<span>cookie</span></div>
        <div style="font-size:9px;color:#9ca3af">Toko Buku Online</div>
    </div>
    <div style="text-align:right">
        <div style="font-size:16px;font-weight:bold;color:#111827">LAPORAN PENJUALAN</div>
        <div style="font-size:10px;color:#6b7280">Periode: '.$period_label.'</div>
        <div style="font-size:9px;color:#9ca3af">Dicetak: '.date('d M Y H:i').'</div>
    </div>
</div>

<h2>Ringkasan</h2>
<table class="stat-grid">
<tr>
    <td class="stat-box"><div class="stat-num">'.($summary['total_orders']??0).'</div><div class="stat-lbl">Total Pesanan</div></td>
    <td class="stat-box"><div class="stat-num">Rp '.number_format($summary['total_revenue']??0,0,',','.').'</div><div class="stat-lbl">Pendapatan</div></td>
    <td class="stat-box"><div class="stat-num">'.($summary['pending']??0).'</div><div class="stat-lbl">Pending</div></td>
    <td class="stat-box"><div class="stat-num">'.($summary['delivered']??0).'</div><div class="stat-lbl">Terkirim</div></td>
    <td class="stat-box"><div class="stat-num">'.($summary['cancelled']??0).'</div><div class="stat-lbl">Dibatalkan</div></td>
</tr>
</table>

<h2>Grafik Pendapatan</h2>
<div class="chart-wrap">'.$svg.'</div>
';

if ($top_rows) {
    $html .= '<h2>5 Buku Terlaris</h2>
    <table><thead><tr><th>#</th><th>Judul</th><th>Penulis</th><th style="text-align:center">Terjual</th><th style="text-align:right">Pendapatan</th></tr></thead>
    <tbody>'.$top_rows.'</tbody></table>';
}

$html .= '<h2>Detail Pesanan ('.count($orders_data).' pesanan)</h2>
<table>
<thead><tr><th>#ID</th><th>User</th><th style="text-align:center">Items</th><th style="text-align:right">Total</th><th style="text-align:center">Status</th><th>Tanggal</th></tr></thead>
<tbody>'.($rows_html ?: '<tr><td colspan="6" style="text-align:center;padding:20px;color:#9ca3af">Tidak ada pesanan</td></tr>').'</tbody>
</table>

<div class="footer">Laporan dibuat otomatis oleh sistem Bucookie &bull; '.date('d M Y H:i').'</div>
</body></html>';

// Generate PDF
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', false);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$filename = 'laporan_bucookie_' . str_replace(' ','_',$period_label) . '_' . date('Ymd') . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);