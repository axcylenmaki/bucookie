<?php
/**
 * admin/includes/sidebar.php
 */

// Helper avatar admin
$_adminAvatarUrl = null;
if (!empty($_SESSION['user_avatar'])) {
    $avatarFile = __DIR__ . '/../../assets/uploads/avatars/' . $_SESSION['user_avatar'];
    if (file_exists($avatarFile)) {
        $_adminAvatarUrl = BASE_URL . 'assets/uploads/avatars/' . htmlspecialchars($_SESSION['user_avatar']);
    }
}
if (!$_adminAvatarUrl && !isset($_SESSION['user_avatar'])) {
    $__s = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
    $__s->bind_param('i', $_SESSION['user_id']);
    $__s->execute();
    $__row = $__s->get_result()->fetch_assoc();
    $__s->close();
    $_SESSION['user_avatar'] = $__row['avatar'] ?? null;
    if (!empty($_SESSION['user_avatar'])) {
        $avatarFile = __DIR__ . '/../../assets/uploads/avatars/' . $_SESSION['user_avatar'];
        if (file_exists($avatarFile)) {
            $_adminAvatarUrl = BASE_URL . 'assets/uploads/avatars/' . htmlspecialchars($_SESSION['user_avatar']);
        }
    }
}
?>
<aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-shield-check"></i>
        </div>
        <div class="brand-text">
            <div class="logo-text">Bu<span>cookie</span></div>
            <div class="admin-badge">Admin Panel</div>
        </div>
    </div>

    <!-- Nav -->
    <nav class="sidebar-nav">

        <div class="nav-label">Utama</div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>admin/index.php"
               class="<?= adminNavActive('index.php', 'admin') ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </div>

        <div class="nav-label">Katalog</div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>admin/categories/index.php"
               class="<?= adminNavActive('', 'categories') ?>">
                <i class="bi bi-tags"></i> Kategori
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>admin/books/index.php"
               class="<?= adminNavActive('', 'books') ?>">
                <i class="bi bi-book"></i> Buku
            </a>
        </div>

        <div class="nav-label">Transaksi</div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>admin/orders/index.php"
               class="<?= adminNavActive('', 'orders') ?>">
                <i class="bi bi-bag-check"></i> Pesanan
                <?php if ($pending_orders > 0): ?>
                <span class="nav-badge"><?= $pending_orders ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="nav-label">Pengguna</div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>admin/users/index.php"
               class="<?= adminNavActive('index.php', 'users') ?>">
                <i class="bi bi-people"></i> User
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>admin/chat/index.php"
               class="<?= adminNavActive('', 'chat') ?>"
               id="sidebarChatLink">
                <i class="bi bi-chat-dots"></i> Chat
                <span class="nav-badge" id="chatBadge" style="display:none"></span>
            </a>
        </div>

        <div class="nav-label">Analitik</div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>admin/reports/index.php"
               class="<?= adminNavActive('', 'reports') ?>">
                <i class="bi bi-bar-chart-line"></i> Laporan
            </a>
        </div>

        <div class="nav-label">Akun</div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>admin/profile.php"
               class="<?= adminNavActive('profile.php', 'admin') ?>">
                <i class="bi bi-person-circle"></i> Profil Saya
            </a>
        </div>

    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>admin/profile.php"
           style="text-decoration:none;display:flex;align-items:center;gap:10px;flex:1;min-width:0">
            <!-- Avatar -->
            <div style="width:34px;height:34px;border-radius:50%;overflow:hidden;flex-shrink:0;border:2px solid var(--border)">
                <?php if ($_adminAvatarUrl): ?>
                <img src="<?= $_adminAvatarUrl ?>" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                <div style="width:100%;height:100%;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:var(--accent)">
                    <?= strtoupper(substr($_SESSION['user_name'], 0, 2)) ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="admin-info" style="min-width:0">
                <div class="admin-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </div>
                <div class="admin-role">Administrator</div>
            </div>
        </a>
        <a href="<?= BASE_URL ?>auth/logout.php" class="btn-logout" style="flex-shrink:0">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>

</aside>