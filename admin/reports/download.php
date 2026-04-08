<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

// --- LOGIKA FILTER (Sama dengan report.php) ---
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
        $period_label = date('d M Y', strtotime($date_from)) . ' – ' . date('d M Y', strtotime($date_to));
        break;
    default:
        $where_date = "AND YEAR(o.created_at)=$filter_year AND MONTH(o.created_at)=$filter_month";
        $period_label = date('F Y', mktime(0,0,0,$filter_month,1,$filter_year));
}

// 1. DATA SUMMARY
$summary = $conn->query("
    SELECT 
        COUNT(DISTINCT o.id) AS total_orders,
        SUM(oi.price * oi.quantity) AS total_revenue,
        SUM((oi.price - b.cost_price) * oi.quantity) AS total_profit
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN books b ON oi.book_id = b.id
    WHERE 1=1 $where_date AND o.status != 'cancelled'
")->fetch_assoc();

// 2. DATA GRAFIK
$chart_select = ($filter_type === 'year') ? "MONTH(o.created_at) AS period" : "DAY(o.created_at) AS period";
$chart_group = ($filter_type === 'year') ? "MONTH(o.created_at)" : "DAY(o.created_at)";
$chart_rows = $conn->query("
    SELECT $chart_select, SUM(oi.price * oi.quantity) AS revenue, SUM((oi.price - b.cost_price) * oi.quantity) AS profit
    FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN books b ON oi.book_id = b.id
    WHERE $where_completed $where_date GROUP BY $chart_group
");

$num_elements = ($filter_type === 'year') ? 12 : cal_days_in_month(CAL_GREGORIAN, $filter_month, $filter_year);
$c_rev = array_fill(1, $num_elements, 0);
$c_prof = array_fill(1, $num_elements, 0);
while ($r = $chart_rows->fetch_assoc()) {
    $c_rev[(int)$r['period']] = (float)$r['revenue'];
    $c_prof[(int)$r['period']] = (float)$r['profit'];
}
$labels = ($filter_type === 'year') ? ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'] : range(1, $num_elements);

// 3. TOP BOOKS
$top_books = $conn->query("
    SELECT b.title, SUM(oi.quantity) AS total_qty, SUM((oi.price - b.cost_price) * oi.quantity) AS book_profit
    FROM order_items oi JOIN books b ON oi.book_id = b.id JOIN orders o ON oi.order_id = o.id
    WHERE $where_completed $where_date GROUP BY b.id ORDER BY total_qty DESC LIMIT 5
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Bucookie - <?= $period_label ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Helvetica', sans-serif; background: #fff; padding: 0; margin: 0; color: #333; }
        #pdf-content { padding: 30px; width: 850px; margin: auto; }
        
        .header { margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 24px; color: #1e293b; }
        .header p { margin: 5px 0; color: #64748b; font-size: 14px; }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; }
        .stat-box { border: 1px solid #e2e8f0; border-radius: 10px; padding: 15px; display: flex; align-items: center; gap: 12px; }
        .stat-box.profit { border: 2px solid #10b981; }
        .stat-val { font-size: 18px; font-weight: bold; display: block; }
        .stat-label { font-size: 11px; color: #64748b; text-transform: uppercase; }

        .chart-section { border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 30px; }
        .chart-title { font-size: 14px; font-weight: bold; margin-bottom: 15px; display: block; }

        .tables-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th { text-align: left; padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; }
        td { padding: 10px; border-bottom: 1px solid #f1f5f9; }
        .badge { background: #f1f5f9; padding: 3px 8px; border-radius: 5px; color: #475569; }
    </style>
</head>
<body>

<div id="pdf-content">
    <div class="header">
        <h1>Laporan Bisnis</h1>
        <p>Periode: <span style="color:#3b82f6; font-weight:bold;"><?= $period_label ?></span></p>
    </div>

    <div class="stats-grid">
        <div class="stat-box">
            <div>
                <span class="stat-val"><?= number_format($summary['total_orders']??0) ?></span>
                <span class="stat-label">Pesanan Sukses</span>
            </div>
        </div>
        <div class="stat-box">
            <div>
                <span class="stat-val">Rp <?= number_format($summary['total_revenue']??0,0,',','.') ?></span>
                <span class="stat-label">Total Omzet</span>
            </div>
        </div>
        <div class="stat-box profit">
            <div>
                <span class="stat-val" style="color:#10b981">Rp <?= number_format($summary['total_profit']??0,0,',','.') ?></span>
                <span class="stat-label">Laba Bersih (Profit)</span>
            </div>
        </div>
    </div>

    <div class="chart-section">
        <span class="chart-title">Tren Penjualan & Keuntungan</span>
        <div style="height: 250px;">
            <canvas id="pdfChart"></canvas>
        </div>
    </div>

    <div class="tables-grid">
        <div>
            <span class="chart-title">5 Buku Terlaris</span>
            <table>
                <thead><tr><th>Judul</th><th>Laku</th><th>Laba</th></tr></thead>
                <tbody>
                    <?php while($b = $top_books->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($b['title']) ?></td>
                        <td><span class="badge"><?= $b['total_qty'] ?> pcs</span></td>
                        <td style="color:#10b981">Rp <?= number_format($b['book_profit'],0,',','.') ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div>
            <span class="chart-title">Detail Performa</span>
            <table>
                <thead><tr><th>Waktu</th><th>Omzet</th><th>Profit</th></tr></thead>
                <tbody>
                    <?php foreach($labels as $idx => $lbl): 
                        if($c_rev[$idx+1] > 0): ?>
                    <tr>
                        <td><strong><?= $lbl ?></strong></td>
                        <td>Rp <?= number_format($c_rev[$idx+1],0,',','.') ?></td>
                        <td style="color:#10b981">Rp <?= number_format($c_prof[$idx+1],0,',','.') ?></td>
                    </tr>
                    <?php endif; endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('pdfChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_values($labels)) ?>,
            datasets: [
                { label: 'Omzet', data: <?= json_encode(array_values($c_rev)) ?>, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.3 },
                { label: 'Profit', data: <?= json_encode(array_values($c_prof)) ?>, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.3 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                onComplete: function() {
                    const element = document.getElementById('pdf-content');
                    const opt = {
                        margin: 0.2,
                        filename: 'Laporan_Bucookie_<?= str_replace(" ", "_", $period_label) ?>.pdf',
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: { scale: 2 },
                        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
                    };
                    html2pdf().set(opt).from(element).save().then(() => {
                        setTimeout(() => { window.close(); }, 1000);
                    });
                }
            }
        }
    });
</script>
</body>
</html>