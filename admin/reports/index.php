<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

// --- LOGIKA FILTER ---
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
    default: // month
        $where_date = "AND YEAR(o.created_at)=$filter_year AND MONTH(o.created_at)=$filter_month";
        $period_label = date('F Y', mktime(0,0,0,$filter_month,1,$filter_year));
}

// 1. RINGKASAN STATISTIK (Termasuk Profit/Laba Bersih)
$summary_query = "
    SELECT 
        COUNT(DISTINCT o.id) AS total_orders,
        SUM(oi.price * oi.quantity) AS total_revenue,
        SUM((oi.price - b.cost_price) * oi.quantity) AS total_profit,
        COUNT(CASE WHEN o.status='pending' THEN 1 END) AS pending,
        COUNT(CASE WHEN o.status='delivered' THEN 1 END) AS delivered
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN books b ON oi.book_id = b.id
    WHERE 1=1 $where_date AND o.status != 'cancelled'
";
$summary = $conn->query($summary_query)->fetch_assoc();

// 2. DATA UNTUK GRAFIK & RINCIAN HARIAN
$chart_select = ($filter_type === 'year') 
    ? "MONTH(o.created_at) AS period" 
    : "DAY(o.created_at) AS period";

$chart_group = ($filter_type === 'year') ? "MONTH(o.created_at)" : "DAY(o.created_at)";

$chart_rows = $conn->query("
    SELECT 
        $chart_select, 
        SUM(oi.price * oi.quantity) AS revenue, 
        SUM((oi.price - b.cost_price) * oi.quantity) AS profit,
        COUNT(DISTINCT o.id) AS order_cnt
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN books b ON oi.book_id = b.id
    WHERE $where_completed $where_date
    GROUP BY $chart_group ORDER BY period ASC
");

// Inisialisasi array data sesuai jumlah hari/bulan
$num_elements = ($filter_type === 'year') ? 12 : cal_days_in_month(CAL_GREGORIAN, $filter_month, $filter_year);
if ($filter_type === 'day') $num_elements = $filter_day; // Untuk filter per hari tunggal

$chart_revenue = array_fill(1, $num_elements, 0);
$chart_profit  = array_fill(1, $num_elements, 0);
$chart_orders  = array_fill(1, $num_elements, 0);

while ($r = $chart_rows->fetch_assoc()) {
    $p = (int)$r['period'];
    if(isset($chart_revenue[$p])) {
        $chart_revenue[$p] = (float)$r['revenue'];
        $chart_profit[$p]  = (float)$r['profit'];
        $chart_orders[$p]  = (int)$r['order_cnt'];
    }
}

$chart_labels = ($filter_type === 'year') 
    ? ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'] 
    : range(1, $num_elements);

// 3. 5 BUKU TERLARIS (TOP LAKU)
$top_books = $conn->query("
    SELECT b.title, SUM(oi.quantity) AS total_qty, 
           SUM((oi.price - b.cost_price) * oi.quantity) AS book_profit
    FROM order_items oi
    JOIN books b ON oi.book_id = b.id
    JOIN orders o ON oi.order_id = o.id
    WHERE $where_completed $where_date
    GROUP BY b.id ORDER BY total_qty DESC LIMIT 5
");
$top_books_data = [];
while ($r = $top_books->fetch_assoc()) $top_books_data[] = $r;

$page_title = 'Laporan Detail';
require_once __DIR__ . '/../../admin/includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 25px;">
    <div>
        <h1 style="margin:0">Laporan Bisnis</h1>
        <p style="color:var(--text-muted)">Periode: <span style="color:var(--accent); font-weight:600"><?= $period_label ?></span></p>
    </div>
    <div style="display:flex; gap:10px">
        <button onclick="window.print()" class="btn-cancel" style="padding: 10px 15px"><i class="bi bi-printer"></i> Cetak</button>
<a href="download.php?<?= http_build_query($_GET) ?>" class="btn-save" style="background:#ef4444; border:none"><i class="bi bi-file-pdf"></i> PDF</a>    </div>
</div>

<div style="background:var(--bg-card); border:1px solid var(--border); border-radius:12px; padding:20px; margin-bottom:25px">
    <form method="GET" style="display:flex; flex-wrap:wrap; gap:15px; align-items:flex-end">
        <div>
            <label style="font-size:11px; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted); display:block; margin-bottom:5px">Mode Laporan</label>
            <select name="filter_type" class="form-select" onchange="this.form.submit()">
                <option value="month" <?= $filter_type==='month'?'selected':'' ?>>Bulanan</option>
                <option value="year" <?= $filter_type==='year'?'selected':'' ?>>Tahunan</option>
                <option value="day" <?= $filter_type==='day'?'selected':'' ?>>Harian</option>
                <option value="range" <?= $filter_type==='range'?'selected':'' ?>>Custom Range</option>
            </select>
        </div>
        
        <?php if($filter_type === 'range'): ?>
            <input type="date" name="date_from" value="<?= $date_from ?>" class="form-control" style="width:160px">
            <input type="date" name="date_to" value="<?= $date_to ?>" class="form-control" style="width:160px">
        <?php else: ?>
            <select name="filter_year" class="form-select" style="width:100px">
                <?php for($y=date('Y'); $y>=2023; $y--): ?>
                    <option value="<?= $y ?>" <?= $filter_year==$y?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <?php if($filter_type != 'year'): ?>
            <select name="filter_month" class="form-select" style="width:130px">
                <?php $months = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; 
                for($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= $filter_month==$m?'selected':'' ?>><?= $months[$m] ?></option>
                <?php endfor; ?>
            </select>
            <?php endif; ?>
            <?php if($filter_type == 'day'): ?>
                <input type="number" name="filter_day" value="<?= $filter_day ?>" min="1" max="31" class="form-control" style="width:80px">
            <?php endif; ?>
        <?php endif; ?>

        <button type="submit" class="btn-save">Terapkan Filter</button>
    </form>
</div>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px; margin-bottom:25px">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-cart-check"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= number_format($summary['total_orders']??0) ?></div>
            <div class="stat-label">Pesanan Sukses</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-wallet2"></i></div>
        <div class="stat-info">
            <div class="stat-num">Rp <?= number_format($summary['total_revenue']??0, 0, ',', '.') ?></div>
            <div class="stat-label">Total Omzet</div>
        </div>
    </div>
    <div class="stat-card" style="border: 2px solid #10b981">
        <div class="stat-icon" style="background: #10b981; color:#fff"><i class="bi bi-cash-coin"></i></div>
        <div class="stat-info">
            <div class="stat-num" style="color:#059669">Rp <?= number_format($summary['total_profit']??0, 0, ',', '.') ?></div>
            <div class="stat-label">Laba Bersih (Profit)</div>
        </div>
    </div>
</div>

<div style="background:var(--bg-card); border:1px solid var(--border); border-radius:12px; padding:25px; margin-bottom:25px">
    <h3 style="margin-top:0; font-size:16px"><i class="bi bi-bar-chart-line me-2"></i>Tren Penjualan & Keuntungan</h3>
    <canvas id="mainChart" height="80"></canvas>
</div>

<div style="display:grid; grid-template-columns: 1fr 1.5fr; gap:25px">
    <div class="table-card">
        <div class="table-card-header">
            <h2><i class="bi bi-star-fill" style="color:#f59e0b"></i> 5 Buku Terlaris</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr><th>Judul Buku</th><th class="text-center">Terjual</th><th>Laba</th></tr>
                </thead>
                <tbody>
                    <?php if(empty($top_books_data)): ?>
                        <tr><td colspan="3" class="text-center">Belum ada data</td></tr>
                    <?php endif; ?>
                    <?php foreach($top_books_data as $bk): ?>
                    <tr>
                        <td style="font-weight:600"><?= htmlspecialchars($bk['title']) ?></td>
                        <td class="text-center"><span class="badge-status badge-processing"><?= $bk['total_qty'] ?> pcs</span></td>
                        <td style="color:#10b981; font-weight:600">Rp <?= number_format($bk['book_profit'],0,',','.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <h2><i class="bi bi-journal-text"></i> Performa Harian/Bulanan</h2>
        </div>
        <div class="table-responsive" style="max-height: 350px; overflow-y:auto">
            <table class="table-hover">
                <thead style="position:sticky; top:0; background:var(--bg-card); z-index:10">
                    <tr><th><?= $filter_type=='year'?'Bulan':'Tanggal' ?></th><th>Pesanan</th><th>Pendapatan</th><th>Profit</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $rev_data = array_values($chart_revenue);
                    $prof_data = array_values($chart_profit);
                    $ord_data = array_values($chart_orders);
                    
                    foreach($chart_labels as $idx => $label): 
                        if($ord_data[$idx] > 0): // Hanya tampilkan yang ada penjualan
                    ?>
                    <tr>
                        <td><strong><?= $label ?></strong></td>
                        <td><?= $ord_data[$idx] ?> order</td>
                        <td>Rp <?= number_format($rev_data[$idx],0,',','.') ?></td>
                        <td style="font-weight:700; color:#10b981">Rp <?= number_format($prof_data[$idx],0,',','.') ?></td>
                    </tr>
                    <?php endif; endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('mainChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_values($chart_labels)) ?>,
        datasets: [
            {
                label: 'Omzet (Pendapatan)',
                data: <?= json_encode(array_values($chart_revenue)) ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.3
            },
            {
                label: 'Laba Bersih (Profit)',
                data: <?= json_encode(array_values($chart_profit)) ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.3
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        return ctx.dataset.label + ': Rp ' + ctx.raw.toLocaleString('id-ID');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + (value / 1000).toLocaleString('id-ID') + 'k';
                    }
                }
            }
        }
    }
});
</script>

<style>
    @media print {
        .btn-save, .btn-cancel, form { display:none !important; }
        body { background: #fff !important; }
        .table-responsive { overflow: visible !important; max-height: none !important; }
    }
    .table-hover tr:hover { background: rgba(0,0,0,0.02); }
    .text-center { text-align: center; }
</style>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>