<?php
/**
 * includes/sidebar.php
 * Sidebar untuk halaman USER (public)
 */

// Helper avatar URL
$_avatarUrl = null;
if (isLoggedIn() && !empty($_SESSION['user_avatar'])) {
    $avatarFile = __DIR__ . '/../assets/uploads/avatars/' . $_SESSION['user_avatar'];
    if (file_exists($avatarFile)) {
        $_avatarUrl = BASE_URL . 'assets/uploads/avatars/' . htmlspecialchars($_SESSION['user_avatar']);
    }
}
if (!$_avatarUrl && isLoggedIn()) {
    // Coba ambil dari DB sekali (untuk sesi yang belum punya session avatar)
    if (!isset($_SESSION['user_avatar'])) {
        $__s = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
        $__s->bind_param('i', $_SESSION['user_id']);
        $__s->execute();
        $__row = $__s->get_result()->fetch_assoc();
        $__s->close();
        $_SESSION['user_avatar'] = $__row['avatar'] ?? null;
        if (!empty($_SESSION['user_avatar'])) {
            $avatarFile = __DIR__ . '/../assets/uploads/avatars/' . $_SESSION['user_avatar'];
            if (file_exists($avatarFile)) {
                $_avatarUrl = BASE_URL . 'assets/uploads/avatars/' . htmlspecialchars($_SESSION['user_avatar']);
            }
        }
    }
}
?>
<aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <a href="<?= BASE_URL ?>index.php" style="text-decoration:none">
            <div class="logo-text">Bu<span>cookie</span></div>
        </a>
        <div class="tagline">Toko Buku Online</div>
    </div>

    <!-- Nav -->
    <nav class="sidebar-nav">

        <div class="nav-label">Menu</div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>index.php" class="<?= navActive('index.php') ?>">
                <i class="bi bi-house"></i> Beranda
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>pages/books.php" class="<?= navActive('books.php') ?>">
                <i class="bi bi-book"></i> Koleksi Buku
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>pages/about.php" class="<?= navActive('about.php') ?>">
                <i class="bi bi-info-circle"></i> Tentang Kami
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>pages/contact.php" class="<?= navActive('contact.php') ?>">
                <i class="bi bi-headset"></i> Bantuan
            </a>
        </div>

        <?php if (isLoggedIn()): ?>
        <div class="nav-label">Akun</div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>pages/cart.php" class="<?= navActive('cart.php') ?>">
                <i class="bi bi-cart3"></i> Keranjang
                <?php if ($cart_count > 0): ?>
                <span class="nav-badge"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>user/orders/index.php" class="<?= navActive('index.php', 'orders') ?>">
                <i class="bi bi-bag-check"></i> Pesanan Saya
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>user/profile.php" class="<?= navActive('profile.php') ?>">
                <i class="bi bi-person-circle"></i> Profil
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>auth/logout.php">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </a>
        </div>
        <?php else: ?>
        <div class="nav-label">Akun</div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>auth/login.php" class="<?= navActive('login.php') ?>">
                <i class="bi bi-box-arrow-in-right"></i> Masuk
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= BASE_URL ?>auth/register.php" class="<?= navActive('register.php') ?>">
                <i class="bi bi-person-plus"></i> Daftar
            </a>
        </div>
        <?php endif; ?>

    </nav>

    <!-- Footer dengan avatar -->
    <div class="sidebar-footer">
        <?php if (isLoggedIn()): ?>
        <a href="<?= BASE_URL ?>user/profile.php" style="text-decoration:none;display:flex;align-items:center;gap:10px">
            <!-- Avatar -->
            <div style="width:34px;height:34px;border-radius:50%;overflow:hidden;flex-shrink:0;border:2px solid var(--border)">
                <?php if ($_avatarUrl): ?>
                <img src="<?= $_avatarUrl ?>" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                <div style="width:100%;height:100%;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:var(--accent)">
                    <?= strtoupper(substr($_SESSION['user_name'], 0, 2)) ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                <div class="user-role"><?= ucfirst($_SESSION['user_role']) ?></div>
            </div>
        </a>
        <?php else: ?>
        <div class="user-badge">
            <div class="user-avatar"><i class="bi bi-person"></i></div>
            <div class="user-info">
                <div class="user-name">Tamu</div>
                <div class="user-role">Belum masuk</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

</aside>