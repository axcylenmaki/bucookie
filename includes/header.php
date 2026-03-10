<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($conn)) require_once __DIR__ . '/../config/db.php';

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

// Cart count
$cart_count = 0;
if (isLoggedIn()) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) AS total FROM cart WHERE user_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $cart_count = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
}

if (!function_exists('navActive')) {
    function navActive(string $page, string $dir = ''): string {
        global $current_page, $current_dir;
        if ($dir && $current_dir === $dir) return 'active';
        if ($current_page === $page) return 'active';
        return '';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — Bucookie' : 'Bucookie — Toko Buku' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main-wrapper">

    <header class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <form action="<?= BASE_URL ?>pages/books.php" method="GET" style="margin:0">
                <div class="search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search"
                           placeholder="Cari judul, penulis..."
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
            </form>
        </div>
        <div class="topbar-actions">
            <?php if (isLoggedIn()): ?>

            <!-- Bell Notifikasi -->
            <div style="position:relative">
                <button id="notifBtn" onclick="toggleNotif()"
                        style="width:36px;height:36px;border-radius:8px;background:var(--bg-card);border:1px solid var(--border);color:var(--text-secondary);font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;position:relative">
                    <i class="bi bi-bell"></i>
                    <span id="notifDot" style="display:none;position:absolute;top:5px;right:5px;width:8px;height:8px;background:#ef4444;border-radius:50%;border:2px solid var(--bg-base)"></span>
                </button>

                <!-- Dropdown -->
                <div id="notifDropdown" style="display:none;position:absolute;right:0;top:calc(100% + 8px);width:310px;background:var(--bg-card);border:1px solid var(--border);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.4);z-index:200;overflow:hidden">
                    <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:.82rem;font-weight:600;color:var(--text-primary)">Notifikasi</span>
                        <button onclick="markAllRead()" style="font-size:.7rem;color:var(--accent);background:none;border:none;cursor:pointer">Tandai dibaca</button>
                    </div>
                    <div id="notifList" style="max-height:300px;overflow-y:auto">
                        <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:.8rem">Memuat...</div>
                    </div>
                </div>
            </div>

            <a href="<?= BASE_URL ?>pages/cart.php" class="btn-icon" title="Keranjang">
                <i class="bi bi-cart3"></i>
                <?php if ($cart_count > 0): ?>
                <span class="badge-dot"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
            <?php else: ?>
            <a href="<?= BASE_URL ?>auth/login.php" class="btn-login">Masuk</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if (isLoggedIn()): ?>
    <script>
    const NOTIF_AJAX = '<?= BASE_URL ?>user/notifications/ajax.php';

    function toggleNotif() {
        const dd = document.getElementById('notifDropdown');
        dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
        if (dd.style.display === 'block') loadNotif();
    }

    document.addEventListener('click', e => {
        const btn = document.getElementById('notifBtn');
        const dd  = document.getElementById('notifDropdown');
        if (btn && dd && !btn.contains(e.target) && !dd.contains(e.target)) {
            dd.style.display = 'none';
        }
    });

    function timeAgo(dt) {
        const diff = Math.floor((Date.now() - new Date(dt.replace(' ','T'))) / 1000);
        if (diff < 60)    return diff + ' detik lalu';
        if (diff < 3600)  return Math.floor(diff/60) + ' menit lalu';
        if (diff < 86400) return Math.floor(diff/3600) + ' jam lalu';
        return Math.floor(diff/86400) + ' hari lalu';
    }

    function loadNotif() {
        fetch(NOTIF_AJAX + '?action=fetch')
            .then(r => r.json())
            .then(data => {
                document.getElementById('notifDot').style.display = data.total > 0 ? 'block' : 'none';
                const list = document.getElementById('notifList');
                if (!data.list.length) {
                    list.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:.8rem">Tidak ada notifikasi</div>';
                    return;
                }
                list.innerHTML = data.list.map(n => {
                    const isMsg   = n.type === 'message';
                    const icon    = isMsg ? 'bi-chat-dots' : 'bi-bag-check';
                    const color   = isMsg ? '#60a5fa' : '#4ade80';
                    const link    = isMsg
                        ? '<?= BASE_URL ?>user/chat/index.php'
                        : '<?= BASE_URL ?>user/orders/index.php';
                    return `
                    <a href="${link}"
                       style="display:flex;gap:12px;align-items:flex-start;padding:12px 16px;border-bottom:1px solid var(--border);text-decoration:none;${isMsg?'background:rgba(59,130,246,.04)':''}">
                        <div style="width:32px;height:32px;border-radius:8px;background:${color}22;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:${color}">
                            <i class="bi ${icon}" style="font-size:.85rem"></i>
                        </div>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:.78rem;color:var(--text-primary);line-height:1.4">${n.message}</div>
                            <div style="font-size:.68rem;color:var(--text-muted);margin-top:3px">${timeAgo(n.created_at)}</div>
                        </div>
                    </a>`;
                }).join('');
            });
    }

    function markAllRead() {
        const fd = new FormData();
        fd.append('action', 'mark_read');
        fetch(NOTIF_AJAX, {method:'POST', body:fd}).then(() => {
            document.getElementById('notifDot').style.display = 'none';
            document.getElementById('notifList').innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:.8rem">Semua sudah dibaca</div>';
        });
    }

    function pollNotif() {
        fetch(NOTIF_AJAX + '?action=fetch')
            .then(r => r.json())
            .then(data => {
                document.getElementById('notifDot').style.display = data.total > 0 ? 'block' : 'none';
            });
    }

    pollNotif();
    setInterval(pollNotif, 5000);
    </script>
    <?php endif; ?>

    <main class="page-content">