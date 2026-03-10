<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

// Filter
$filter_type  = $_GET['filter_type']  ?? 'month'; // day, month, year, range
$filter_year  = (int)($_GET['filter_year']  ?? date('Y'));
$filter_month = (int)($_GET['filter_month'] ?? date('n'));
$filter_day   = (int)($_GET['filter_day']   ?? date('j'));
$date_from    = $_GET['date_from'] ?? date('Y-m-01');
$date_to      = $_GET['date_to']   ?? date('Y-m-d');

// Bangun WHERE clause
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

// Total ringkasan
$summary = $conn->query("
    SELECT
        COUNT(*) AS total_orders,
        SUM(o.total_price) AS total_revenue,
        COUNT(CASE WHEN o.status='pending' THEN 1 END) AS pending,
        COUNT(CASE WHEN o.status='cancelled' THEN 1 END) AS cancelled,
        COUNT(CASE WHEN o.status='delivered' THEN 1 END) AS delivered
    FROM orders o
    WHERE 1=1 $where_date
")->fetch_assoc();

// Data tabel pesanan
$orders = $conn->query("
    SELECT o.id, u.name AS user_name, o.total_price, o.status, o.created_at,
           COUNT(oi.id) AS total_items
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE 1=1 $where_date
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$orders_data = [];
while ($r = $orders->fetch_assoc()) $orders_data[] = $r;

// Data chart: pendapatan per hari/bulan
if ($filter_type === 'year') {
    $chart_rows = $conn->query("
        SELECT MONTH(o.created_at) AS period, SUM(o.total_price) AS revenue, COUNT(*) AS cnt
        FROM orders o
        WHERE $where_completed $where_date
        GROUP BY MONTH(o.created_at) ORDER BY period ASC
    ");
    $chart_labels_all = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
    $chart_data = array_fill(1,12,0);
    $chart_cnt  = array_fill(1,12,0);
    while ($r = $chart_rows->fetch_assoc()) { $chart_data[(int)$r['period']] = (float)$r['revenue']; $chart_cnt[(int)$r['period']] = (int)$r['cnt']; }
    $chart_labels  = $chart_labels_all;
    $chart_revenue = array_values($chart_data);
    $chart_orders  = array_values($chart_cnt);
} else {
    // Per hari dalam bulan/range
    $chart_rows = $conn->query("
        SELECT DAY(o.created_at) AS period, SUM(o.total_price) AS revenue, COUNT(*) AS cnt
        FROM orders o
        WHERE $where_completed $where_date
        GROUP BY DAY(o.created_at) ORDER BY period ASC
    ");
    $days = $filter_type === 'day' ? 1 : cal_days_in_month(CAL_GREGORIAN, $filter_month, $filter_year);
    $chart_data = array_fill(1,$days,0);
    $chart_cnt  = array_fill(1,$days,0);
    while ($r = $chart_rows->fetch_assoc()) { $chart_data[(int)$r['period']] = (float)$r['revenue']; $chart_cnt[(int)$r['period']] = (int)$r['cnt']; }
    $chart_labels  = array_map(fn($d) => $d, range(1,$days));
    $chart_revenue = array_values($chart_data);
    $chart_orders  = array_values($chart_cnt);
}

// Buku terlaris
$top_books = $conn->query("
    SELECT b.title, b.author, SUM(oi.quantity) AS total_qty, SUM(oi.quantity*oi.price) AS total_rev
    FROM order_items oi
    JOIN books b ON oi.book_id = b.id
    JOIN orders o ON oi.order_id = o.id
    WHERE $where_completed $where_date
    GROUP BY b.id ORDER BY total_qty DESC LIMIT 5
");
$top_books_data = [];
while ($r = $top_books->fetch_assoc()) $top_books_data[] = $r;

$page_title = 'Laporan';
require_once __DIR__ . '/../../admin/includes/header.php';
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
    <div>
        <h1>Laporan Penjualan</h1>
        <p>Periode: <strong style="color:var(--text-primary)"><?= $period_label ?></strong></p>
    </div>
    <a href="<?= BASE_URL ?>admin/reports/download.php?<?= http_build_query($_GET) ?>"
       target="_blank"
       style="display:inline-flex;align-items:center;gap:7px;padding:9px 18px;background:#ef4444;color:#fff;border-radius:8px;text-decoration:none;font-size:.83rem;font-weight:500">
        <i class="bi bi-file-pdf"></i> Download PDF
    </a>
</div>

<!-- Filter Bar -->
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:16px 20px;margin-bottom:24px">
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
        <div>
            <label style="font-size:.72rem;color:var(--text-muted);display:block;margin-bottom:5px">Tipe Filter</label>
            <select name="filter_type" class="form-select" style="width:130px" onchange="this.form.submit()">
                <option value="month"  <?= $filter_type==='month'  ?'selected':'' ?>>Per Bulan</option>
                <option value="year"   <?= $filter_type==='year'   ?'selected':'' ?>>Per Tahun</option>
                <option value="day"    <?= $filter_type==='day'    ?'selected':'' ?>>Per Hari</option>
                <option value="range"  <?= $filter_type==='range'  ?'selected':'' ?>>Rentang Tanggal</option>
            </select>
        </div>

        <?php if ($filter_type === 'range'): ?>
        <div>
            <label style="font-size:.72rem;color:var(--text-muted);display:block;margin-bottom:5px">Dari</label>
            <input type="date" name="date_from" value="<?= $date_from ?>" class="form-control" style="width:150px">
        </div>
        <div>
            <label style="font-size:.72rem;color:var(--text-muted);display:block;margin-bottom:5px">Sampai</label>
            <input type="date" name="date_to" value="<?= $date_to ?>" class="form-control" style="width:150px">
        </div>
        <?php else: ?>
        <div>
            <label style="font-size:.72rem;color:var(--text-muted);display:block;margin-bottom:5px">Tahun</label>
            <select name="filter_year" class="form-select" style="width:100px">
                <?php for ($y = date('Y'); $y >= 2023; $y--): ?>
                <option value="<?= $y ?>" <?= $filter_year===$y?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <?php if (in_array($filter_type,['month','day'])): ?>
        <div>
            <label style="font-size:.72rem;color:var(--text-muted);display:block;margin-bottom:5px">Bulan</label>
            <select name="filter_month" class="form-select" style="width:130px">
                <?php $bln = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
                for ($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $filter_month===$m?'selected':'' ?>><?= $bln[$m] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <?php endif; ?>
        <?php if ($filter_type==='day'): ?>
        <div>
            <label style="font-size:.72rem;color:var(--text-muted);display:block;margin-bottom:5px">Hari</label>
            <input type="number" name="filter_day" value="<?= $filter_day ?>" min="1" max="31" class="form-control" style="width:80px">
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <button type="submit" class="btn-save" style="padding:8px 18px">
            <i class="bi bi-funnel"></i> Filter
        </button>
    </form>
</div>

<!-- Stat Cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-bottom:24px">
    <?php
    $stats = [
        ['icon'=>'bi-bag-check','color'=>'blue', 'num'=>$summary['total_orders']??0,     'label'=>'Total Pesanan'],
        ['icon'=>'bi-cash-stack','color'=>'green','num'=>'Rp '.number_format($summary['total_revenue']??0,0,',','.'),'label'=>'Total Pendapatan'],
        ['icon'=>'bi-clock',    'color'=>'yellow','num'=>$summary['pending']??0,          'label'=>'Pending'],
        ['icon'=>'bi-check2-circle','color'=>'green','num'=>$summary['delivered']??0,     'label'=>'Terkirim'],
        ['icon'=>'bi-x-circle', 'color'=>'red',  'num'=>$summary['cancelled']??0,         'label'=>'Dibatalkan'],
    ];
    foreach ($stats as $st): ?>
    <div class="stat-card">
        <div class="stat-icon <?= $st['color'] ?>"><i class="bi <?= $st['icon'] ?>"></i></div>
        <div class="stat-info">
            <div class="stat-num" style="font-size:1.1rem"><?= $st['num'] ?></div>
            <div class="stat-label"><?= $st['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Chart -->
<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:24px">
    <div style="font-size:.85rem;font-weight:600;color:var(--text-primary);margin-bottom:16px;display:flex;align-items:center;gap:8px">
        <i class="bi bi-bar-chart" style="color:var(--accent)"></i> Grafik Pendapatan
    </div>
    <canvas id="revenueChart" height="90"></canvas>
</div>

<!-- Buku Terlaris -->
<?php if (!empty($top_books_data)): ?>
<div class="table-card" style="margin-bottom:24px">
    <div class="table-card-header">
        <h2><i class="bi bi-trophy me-2" style="color:#f59e0b"></i>5 Buku Terlaris</h2>
    </div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>#</th><th>Judul</th><th>Penulis</th><th>Terjual</th><th>Pendapatan</th></tr></thead>
            <tbody>
                <?php foreach ($top_books_data as $i => $bk): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td class="td-bold"><?= htmlspecialchars($bk['title']) ?></td>
                    <td><?= htmlspecialchars($bk['author']) ?></td>
                    <td><span class="badge-status badge-processing"><?= $bk['total_qty'] ?> pcs</span></td>
                    <td class="td-bold">Rp <?= number_format($bk['total_rev'],0,',','.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Tabel Pesanan -->
<div class="table-card">
    <div class="table-card-header">
        <h2><i class="bi bi-list-ul me-2"></i>Detail Pesanan</h2>
        <span style="font-size:.75rem;color:var(--text-muted)"><?= count($orders_data) ?> pesanan</span>
    </div>
    <div class="table-responsive">
        <?php if (!$orders_data): ?>
        <div class="empty-state"><i class="bi bi-inbox"></i><p>Tidak ada pesanan di periode ini</p></div>
        <?php else: ?>
        <table>
            <thead><tr><th>#ID</th><th>User</th><th>Items</th><th>Total</th><th>Status</th><th>Tanggal</th></tr></thead>
            <tbody>
                <?php foreach ($orders_data as $o): ?>
                <tr>
                    <td class="td-bold">#<?= $o['id'] ?></td>
                    <td><?= htmlspecialchars($o['user_name']) ?></td>
                    <td><?= $o['total_items'] ?> buku</td>
                    <td class="td-bold">Rp <?= number_format($o['total_price'],0,',','.') ?></td>
                    <td><span class="badge-status badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td style="font-size:.78rem"><?= date('d M Y H:i', strtotime($o['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labels  = <?= json_encode($chart_labels) ?>;
const revenue = <?= json_encode($chart_revenue) ?>;
const orders  = <?= json_encode($chart_orders) ?>;

const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels,
        datasets: [
            {
                label: 'Pendapatan (Rp)',
                data: revenue,
                backgroundColor: 'rgba(59,130,246,0.7)',
                borderColor: '#3b82f6',
                borderWidth: 1,
                borderRadius: 4,
                yAxisID: 'y'
            },
            {
                label: 'Jumlah Pesanan',
                data: orders,
                type: 'line',
                borderColor: '#4ade80',
                backgroundColor: 'rgba(74,222,128,0.1)',
                borderWidth: 2,
                pointRadius: 3,
                tension: 0.3,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { labels: { color: '#7a8fa6', font: { family: 'Sora', size: 11 } } },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.datasetIndex===0
                        ? ' Rp ' + parseInt(ctx.raw).toLocaleString('id-ID')
                        : ' ' + ctx.raw + ' pesanan'
                }
            }
        },
        scales: {
            x:  { ticks: { color: '#7a8fa6', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.04)' } },
            y:  { ticks: { color: '#7a8fa6', font: { size: 10 }, callback: v => 'Rp ' + (v/1000).toFixed(0) + 'k' }, grid: { color: 'rgba(255,255,255,0.04)' }, position: 'left' },
            y1: { ticks: { color: '#4ade80', font: { size: 10 } }, grid: { display: false }, position: 'right' }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>