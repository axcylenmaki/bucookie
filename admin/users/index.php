<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

$search = trim($_GET['search'] ?? '');

$page_title = 'User';
require_once __DIR__ . '/../../admin/includes/header.php';

$where = "WHERE u.role = 'user'";
if ($search) {
    $s     = $conn->real_escape_string($search);
    $where .= " AND (u.name LIKE '%$s%' OR u.email LIKE '%$s%')";
}

$users = $conn->query("
    SELECT u.*, COUNT(o.id) AS total_orders
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id
    $where
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
?>

<div class="page-header">
    <h1>User Terdaftar</h1>
    <p>Daftar semua user yang sudah registrasi</p>
</div>

<div class="table-card">
    <div class="table-card-header">
        <h2><i class="bi bi-people me-2"></i>Daftar User</h2>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <div style="position:relative">
                <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.85rem"></i>
                <input type="text" name="search" class="form-control"
                       style="padding-left:32px;width:220px;padding-top:7px;padding-bottom:7px"
                       placeholder="Cari nama / email..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn-save" style="padding:7px 14px">Cari</button>
            <?php if ($search): ?>
            <a href="<?= BASE_URL ?>admin/users/index.php" class="btn-cancel" style="margin:0">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <?php if ($users->num_rows === 0): ?>
        <div class="empty-state">
            <i class="bi bi-people"></i>
            <p>Belum ada user terdaftar</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>No. HP</th>
                    <th>Alamat</th>
                    <th>Total Pesanan</th>
                    <th>Bergabung</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($user = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:30px;height:30px;border-radius:50%;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:var(--accent);flex-shrink:0">
                                <?= strtoupper(substr($user['name'], 0, 2)) ?>
                            </div>
                            <span class="td-bold"><?= htmlspecialchars($user['name']) ?></span>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= $user['phone'] ? htmlspecialchars($user['phone']) : '<span style="color:var(--text-muted)">-</span>' ?></td>
                    <td style="max-width:150px;font-size:.78rem"><?= $user['address'] ? htmlspecialchars($user['address']) : '<span style="color:var(--text-muted)">-</span>' ?></td>
                    <td><span class="badge-status badge-processing"><?= $user['total_orders'] ?> pesanan</span></td>
                    <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>