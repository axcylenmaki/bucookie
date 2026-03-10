<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

$msg = $_GET['msg'] ?? '';

$page_title = 'Kategori';
$btn_add    = ['url' => BASE_URL . 'admin/categories/add.php', 'label' => 'Tambah Kategori'];
require_once __DIR__ . '/../../admin/includes/header.php';

$categories = $conn->query("
    SELECT c.*, COUNT(b.id) AS total_books
    FROM categories c
    LEFT JOIN books b ON b.category_id = c.id
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
?>

<div class="page-header">
    <div>
        <h1>Kategori Buku</h1>
        <p>Kelola kategori untuk pengelompokan buku</p>
    </div>
    <a href="<?= BASE_URL ?>admin/categories/add.php" class="btn-save" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px">
        <i class="bi bi-plus-lg"></i> Tambah Kategori
    </a>
</div>

<?php if ($msg === 'added'): ?>
<div class="alert alert-success"><i class="bi bi-check-circle"></i> Kategori berhasil ditambahkan.</div>
<?php elseif ($msg === 'updated'): ?>
<div class="alert alert-success"><i class="bi bi-check-circle"></i> Kategori berhasil diperbarui.</div>
<?php elseif ($msg === 'deleted'): ?>
<div class="alert alert-success"><i class="bi bi-check-circle"></i> Kategori berhasil dihapus.</div>
<?php elseif ($msg === 'has_books'): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Kategori tidak bisa dihapus karena masih memiliki buku.</div>
<?php endif; ?>

<div class="table-card">
    <div class="table-card-header">
        <h2><i class="bi bi-tags me-2"></i>Daftar Kategori</h2>
    </div>
    <div class="table-responsive">
        <?php if ($categories->num_rows === 0): ?>
        <div class="empty-state">
            <i class="bi bi-tags"></i>
            <p>Belum ada kategori. <a href="<?= BASE_URL ?>admin/categories/add.php" style="color:var(--accent)">Tambah sekarang</a></p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Buku</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($cat = $categories->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="td-bold"><?= htmlspecialchars($cat['name']) ?></td>
                    <td><?= $cat['description'] ? htmlspecialchars($cat['description']) : '<span style="color:var(--text-muted)">-</span>' ?></td>
                    <td><span class="badge-status badge-processing"><?= $cat['total_books'] ?> buku</span></td>
                    <td><?= date('d M Y', strtotime($cat['created_at'])) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>admin/categories/edit.php?id=<?= $cat['id'] ?>" class="btn-action btn-edit">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="<?= BASE_URL ?>admin/categories/delete.php?id=<?= $cat['id'] ?>"
                           class="btn-action btn-delete ms-1"
                           onclick="return confirm('Hapus kategori <?= addslashes($cat['name']) ?>?')">
                            <i class="bi bi-trash"></i> Hapus
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>