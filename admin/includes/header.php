<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

function adminNavActive(string $page, string $dir = ''): string {
    global $current_page, $current_dir;
    if ($dir && $current_dir === $dir) return 'active';
    if ($current_page === $page) return 'active';
    return '';
}

$pending_orders = (int)$conn->query("SELECT COUNT(*) AS c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — Bucookie Admin' : 'Bucookie Admin' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bg-base:       #0d1117;
            --bg-sidebar:    #0b1520;
            --bg-card:       #111a26;
            --bg-card-hover: #152030;
            --border:        rgba(255,255,255,0.06);
            --accent:        #3b82f6;
            --accent-soft:   rgba(59,130,246,0.12);
            --accent-glow:   rgba(59,130,246,0.2);
            --danger:        #ef4444;
            --danger-soft:   rgba(239,68,68,0.1);
            --success:       #22c55e;
            --success-soft:  rgba(34,197,94,0.1);
            --warning:       #f59e0b;
            --warning-soft:  rgba(245,158,11,0.1);
            --text-primary:  #e8edf3;
            --text-secondary:#7a8fa6;
            --text-muted:    #3d5066;
            --sidebar-w:     255px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--bg-base);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 100;
            transition: transform .3s ease;
        }

        .sidebar-brand {
            padding: 24px 20px 18px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-icon {
            width: 34px; height: 34px;
            background: var(--accent-soft);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: var(--accent);
            font-size: 1rem;
            flex-shrink: 0;
        }

        .brand-text .logo-text {
            font-family: 'Lora', serif;
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .brand-text .logo-text span { color: var(--accent); }

        .brand-text .admin-badge {
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-top: 1px;
        }

        .sidebar-nav {
            padding: 16px 10px;
            flex: 1;
            overflow-y: auto;
        }

        .nav-label {
            font-size: 0.62rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--text-muted);
            padding: 0 10px;
            margin: 14px 0 6px;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 10px;
            border-radius: 7px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.845rem;
            font-weight: 400;
            transition: all .2s ease;
        }

        .nav-item a:hover {
            background: var(--accent-soft);
            color: var(--text-primary);
        }

        .nav-item a.active {
            background: var(--accent-soft);
            color: var(--accent);
            font-weight: 500;
        }

        .nav-item a.active i, .nav-item a:hover i { color: inherit; }

        .nav-item i {
            font-size: 0.95rem;
            width: 17px;
            text-align: center;
            color: var(--text-muted);
            flex-shrink: 0;
        }

        .nav-badge {
            margin-left: auto;
            background: var(--danger);
            color: #fff;
            font-size: 0.6rem;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 999px;
            line-height: 1.4;
        }

        .sidebar-footer {
            padding: 12px 10px 20px;
            border-top: 1px solid var(--border);
        }

        .admin-badge-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: 8px;
            background: var(--bg-card);
            margin-bottom: 8px;
        }

        .admin-avatar {
            width: 30px; height: 30px;
            border-radius: 50%;
            background: var(--accent-soft);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem;
            color: var(--accent);
            font-weight: 700;
            flex-shrink: 0;
        }

        .admin-info .admin-name {
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--text-primary);
            line-height: 1;
        }

        .admin-info .admin-role {
            font-size: 0.65rem;
            color: var(--accent);
            margin-top: 2px;
        }

        .btn-logout {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 8px 10px;
            border-radius: 7px;
            background: var(--danger-soft);
            border: 1px solid rgba(239,68,68,0.15);
            color: var(--danger);
            font-family: 'Sora', sans-serif;
            font-size: 0.8rem;
            cursor: pointer;
            text-decoration: none;
            transition: all .2s;
        }

        .btn-logout:hover {
            background: rgba(239,68,68,0.2);
            color: var(--danger);
        }

        /* ── MAIN ── */
        .main-wrapper {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            background: var(--bg-base);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 4px;
        }

        .page-breadcrumb {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .page-breadcrumb span {
            color: var(--text-primary);
            font-weight: 500;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-topbar {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 7px;
            font-family: 'Sora', sans-serif;
            font-size: 0.78rem;
            font-weight: 500;
            text-decoration: none;
            transition: all .2s;
            border: 1px solid var(--border);
            background: var(--bg-card);
            color: var(--text-secondary);
        }

        .btn-topbar:hover {
            background: var(--accent-soft);
            color: var(--accent);
            border-color: var(--accent);
        }

        .btn-topbar.primary {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }

        .btn-topbar.primary:hover {
            background: #2563eb;
            color: #fff;
        }

        /* ── PAGE CONTENT ── */
        .page-content {
            padding: 28px;
            flex: 1;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-header h1 {
            font-family: 'Lora', serif;
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .page-header p {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        /* ── STAT CARDS ── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: border-color .2s;
        }

        .stat-card:hover { border-color: rgba(59,130,246,0.2); }

        .stat-icon {
            width: 42px; height: 42px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .stat-icon.blue   { background: var(--accent-soft);  color: var(--accent); }
        .stat-icon.green  { background: var(--success-soft); color: var(--success); }
        .stat-icon.yellow { background: var(--warning-soft); color: var(--warning); }
        .stat-icon.red    { background: var(--danger-soft);  color: var(--danger); }

        .stat-info .stat-num {
            font-family: 'Lora', serif;
            font-size: 1.6rem;
            font-weight: 600;
            line-height: 1;
            color: var(--text-primary);
        }

        .stat-info .stat-label {
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }

        /* ── TABLE ── */
        .table-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .table-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .table-card-header h2 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .table-responsive { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            padding: 11px 20px;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
            font-weight: 500;
        }

        tbody td {
            padding: 13px 20px;
            font-size: 0.83rem;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tbody tr:last-child td { border-bottom: none; }

        tbody tr:hover td { background: var(--bg-card-hover); }

        .td-bold { color: var(--text-primary); font-weight: 500; }

        /* ── BADGES ── */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 500;
        }

        .badge-status::before {
            content: '';
            width: 5px; height: 5px;
            border-radius: 50%;
            background: currentColor;
        }

        .badge-pending    { background: var(--warning-soft); color: var(--warning); }
        .badge-processing { background: var(--accent-soft);  color: var(--accent); }
        .badge-shipped    { background: rgba(139,92,246,0.1); color: #a78bfa; }
        .badge-delivered  { background: var(--success-soft); color: var(--success); }
        .badge-cancelled  { background: var(--danger-soft);  color: var(--danger); }
        .badge-admin      { background: var(--accent-soft);  color: var(--accent); }
        .badge-user       { background: var(--success-soft); color: var(--success); }

        /* ── ACTION BUTTONS ── */
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 6px;
            font-family: 'Sora', sans-serif;
            font-size: 0.75rem;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-edit   { background: var(--accent-soft);  color: var(--accent); }
        .btn-delete { background: var(--danger-soft);  color: var(--danger); }
        .btn-view   { background: var(--success-soft); color: var(--success); }

        .btn-edit:hover   { background: var(--accent);  color: #fff; }
        .btn-delete:hover { background: var(--danger);  color: #fff; }
        .btn-view:hover   { background: var(--success); color: #fff; }

        /* ── FORM ── */
        .form-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .form-label {
            font-size: 0.78rem;
            color: var(--text-secondary);
            margin-bottom: 6px;
            display: block;
        }

        .form-label .req { color: #f87171; margin-left: 2px; }

        .form-control, .form-select {
            width: 100%;
            background: var(--bg-base);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 9px 13px;
            color: var(--text-primary);
            font-family: 'Sora', sans-serif;
            font-size: 0.855rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .form-control::placeholder { color: var(--text-muted); }

        .form-select option { background: var(--bg-card); }

        textarea.form-control { resize: vertical; min-height: 90px; }

        .mb-field { margin-bottom: 16px; }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
        }

        .btn-save {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 22px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: 'Sora', sans-serif;
            font-size: 0.855rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-save:hover { background: #2563eb; transform: translateY(-1px); }

        .btn-cancel {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: 'Sora', sans-serif;
            font-size: 0.855rem;
            text-decoration: none;
            transition: all .2s;
            margin-left: 8px;
        }

        .btn-cancel:hover {
            background: var(--bg-card-hover);
            color: var(--text-primary);
        }

        /* ── ALERT ── */
        .alert {
            padding: 11px 16px;
            border-radius: 8px;
            font-size: 0.82rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .alert-success { background: var(--success-soft); color: var(--success); border: 1px solid rgba(34,197,94,0.2); }
        .alert-danger  { background: var(--danger-soft);  color: var(--danger);  border: 1px solid rgba(239,68,68,0.2); }
        .alert-warning { background: var(--warning-soft); color: var(--warning); border: 1px solid rgba(245,158,11,0.2); }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-muted);
        }

        .empty-state i { font-size: 2.2rem; margin-bottom: 10px; display: block; }
        .empty-state p { font-size: 0.82rem; }

        /* ── SIDEBAR OVERLAY (mobile) ── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 99;
        }

        /* ── SCROLLBAR ── */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: var(--bg-base); }
        ::-webkit-scrollbar-thumb { background: var(--text-muted); border-radius: 999px; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .sidebar-toggle { display: block; }
            .main-wrapper { margin-left: 0; }
            .page-content { padding: 16px; }
            .topbar { padding: 12px 16px; }
            .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
            .stat-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<?php require_once __DIR__ . '/../../admin/includes/sidebar.php'; ?>

<div class="main-wrapper">

    <header class="topbar">
        <div style="display:flex;align-items:center;gap:12px">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <div style="font-size:.82rem;font-weight:500;color:var(--text-primary)"><?= isset($page_title) ? htmlspecialchars($page_title) : 'Admin' ?></div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
            <!-- Bell Notifikasi -->
            <div style="position:relative">
                <button id="notifBtn" onclick="toggleNotif()"
                        style="width:36px;height:36px;border-radius:8px;background:var(--bg-card);border:1px solid var(--border);color:var(--text-secondary);font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;position:relative">
                    <i class="bi bi-bell"></i>
                    <span id="notifDot" style="display:none;position:absolute;top:5px;right:5px;width:8px;height:8px;background:#ef4444;border-radius:50%;border:2px solid var(--bg-base)"></span>
                </button>
                <!-- Dropdown notifikasi -->
                <div id="notifDropdown" style="display:none;position:absolute;right:0;top:calc(100% + 8px);width:320px;background:var(--bg-card);border:1px solid var(--border);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.4);z-index:200;overflow:hidden">
                    <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:.82rem;font-weight:600;color:var(--text-primary)">Notifikasi</span>
                        <button onclick="markAllRead()" style="font-size:.7rem;color:var(--accent);background:none;border:none;cursor:pointer">Tandai semua dibaca</button>
                    </div>
                    <div id="notifList" style="max-height:320px;overflow-y:auto">
                        <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:.8rem">Memuat...</div>
                    </div>
                </div>
            </div>

            <?php if ($pending_orders > 0): ?>
            <a href="<?= BASE_URL ?>admin/orders/index.php?status=pending"
               style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:var(--warning-soft);color:var(--warning);border-radius:7px;text-decoration:none;font-size:.75rem;font-weight:500">
                <i class="bi bi-bag"></i> <?= $pending_orders ?> pesanan baru
            </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>index.php"
               style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:var(--bg-card);border:1px solid var(--border);color:var(--text-secondary);border-radius:7px;text-decoration:none;font-size:.75rem">
                <i class="bi bi-shop"></i> Lihat Toko
            </a>
        </div>
    </header>

    <!-- Script Notifikasi -->
    <script>
    const NOTIF_AJAX = '<?= BASE_URL ?>admin/notifications/ajax.php';
    const CHAT_AJAX  = '<?= BASE_URL ?>admin/chat/ajax.php';

    function toggleNotif() {
        const dd = document.getElementById('notifDropdown');
        dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
        if (dd.style.display === 'block') loadNotif();
    }

    document.addEventListener('click', e => {
        if (!document.getElementById('notifBtn').contains(e.target) &&
            !document.getElementById('notifDropdown').contains(e.target)) {
            document.getElementById('notifDropdown').style.display = 'none';
        }
    });

    function loadNotif() {
        fetch(NOTIF_AJAX + '?action=fetch')
            .then(r=>r.json())
            .then(data => {
                const dot = document.getElementById('notifDot');
                dot.style.display = data.total > 0 ? 'block' : 'none';

                const list = document.getElementById('notifList');
                if (!data.list.length) {
                    list.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:.8rem">Tidak ada notifikasi baru</div>';
                    return;
                }
                list.innerHTML = data.list.map(n => {
                    const icon = n.type === 'order' ? 'bi-bag-check' : 'bi-chat-dots';
                    const color = n.type === 'order' ? '#f59e0b' : '#60a5fa';
                    const link = n.type === 'order'
                        ? '<?= BASE_URL ?>admin/orders/index.php?status=pending'
                        : '<?= BASE_URL ?>admin/chat/index.php';
                    return `<a href="${link}" onclick="markOne(${n.id})"
                        style="display:flex;gap:12px;align-items:flex-start;padding:12px 16px;border-bottom:1px solid var(--border);text-decoration:none;background:rgba(59,130,246,.04)">
                        <div style="width:32px;height:32px;border-radius:8px;background:${color}22;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:${color}">
                            <i class="bi ${icon}" style="font-size:.85rem"></i>
                        </div>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:.78rem;color:var(--text-primary);line-height:1.4">${n.message}</div>
                            <div style="font-size:.68rem;color:var(--text-muted);margin-top:3px">${n.created_at}</div>
                        </div>
                    </a>`;
                }).join('');
            });
    }

    function markAllRead() {
        const fd = new FormData(); fd.append('action','mark_read');
        fetch(NOTIF_AJAX, {method:'POST',body:fd}).then(()=>{
            document.getElementById('notifDot').style.display = 'none';
            document.getElementById('notifList').innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:.8rem">Semua sudah dibaca</div>';
        });
    }

    function markOne(id) {
        const fd = new FormData(); fd.append('action','mark_one'); fd.append('id',id);
        fetch(NOTIF_AJAX, {method:'POST',body:fd});
    }

    // Poll notifikasi + chat badge
    function pollBadges() {
        fetch(NOTIF_AJAX + '?action=fetch')
            .then(r=>r.json())
            .then(data => {
                document.getElementById('notifDot').style.display = data.total > 0 ? 'block' : 'none';
            });
        fetch(CHAT_AJAX + '?action=user_list')
            .then(r=>r.json())
            .then(list => {
                const total = list.reduce((s,u)=>s+parseInt(u.unread||0),0);
                const badge = document.getElementById('chatBadge');
                if (badge) { badge.textContent = total; badge.style.display = total > 0 ? 'inline' : 'none'; }
            });
    }

    pollBadges();
    setInterval(pollBadges, 5000);
    </script>

    <main class="page-content">