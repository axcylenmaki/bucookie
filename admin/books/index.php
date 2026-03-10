<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

$msg    = $_GET['msg']    ?? '';
$search = trim($_GET['search'] ?? '');

$page_title = 'Buku';
$btn_add    = ['url' => BASE_URL . 'admin/books/add.php', 'label' => 'Tambah Buku'];
require_once __DIR__ . '/../../admin/includes/header.php';

$where = '';
if ($search) {
    $s     = $conn->real_escape_string($search);
    $where = "WHERE b.title LIKE '%$s%' OR b.author LIKE '%$s%'";
}

$books = $conn->query("
    SELECT b.*, c.name AS category_name
    FROM books b
    JOIN categories c ON b.category_id = c.id
    $where
    ORDER BY b.created_at DESC
");
?>

<div class="page-header">
    <div>
        <h1>Buku</h1>
        <p>Kelola data buku yang tersedia di toko</p>
    </div>
    <a href="<?= BASE_URL ?>admin/books/add.php" class="btn-save" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px">
        <i class="bi bi-plus-lg"></i> Tambah Buku
    </a>
</div>

<?php if ($msg === 'added'): ?>
<div class="alert alert-success"><i class="bi bi-check-circle"></i> Buku berhasil ditambahkan.</div>
<?php elseif ($msg === 'updated'): ?>
<div class="alert alert-success"><i class="bi bi-check-circle"></i> Buku berhasil diperbarui.</div>
<?php elseif ($msg === 'deleted'): ?>
<div class="alert alert-success"><i class="bi bi-check-circle"></i> Buku berhasil dihapus.</div>
<?php elseif ($msg === 'has_stock'): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> Buku tidak bisa dihapus karena masih memiliki stok. Kosongkan stok terlebih dahulu.</div>
<?php endif; ?>

<div class="table-card">
    <div class="table-card-header">
        <h2><i class="bi bi-book me-2"></i>Daftar Buku</h2>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <div style="position:relative">
                <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.85rem"></i>
                <input type="text" name="search" class="form-control"
                       style="padding-left:32px;width:220px;padding-top:7px;padding-bottom:7px"
                       placeholder="Cari judul / penulis..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn-save" style="padding:7px 14px">Cari</button>
            <?php if ($search): ?>
            <a href="<?= BASE_URL ?>admin/books/index.php" class="btn-cancel" style="margin:0">Reset</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <?php if ($books->num_rows === 0): ?>
        <div class="empty-state">
            <i class="bi bi-book"></i>
            <p>Belum ada buku. <a href="<?= BASE_URL ?>admin/books/add.php" style="color:var(--accent)">Tambah sekarang</a></p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cover</th>
                    <th>Judul</th>
                    <th>Penulis</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($book = $books->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <?php if (!empty($book['cover']) && file_exists(__DIR__ . '/../../assets/uploads/covers/' . $book['cover'])): ?>
                        <img src="<?= BASE_URL ?>assets/uploads/covers/<?= htmlspecialchars($book['cover']) ?>"
                             style="width:36px;height:48px;object-fit:cover;border-radius:4px;border:1px solid var(--border)">
                        <?php else: ?>
                        <div style="width:36px;height:48px;background:var(--bg-base);border-radius:4px;border:1px solid var(--border);display:flex;align-items:center;justify-content:center">
                            <i class="bi bi-book" style="color:var(--text-muted);font-size:.75rem"></i>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="td-bold" style="max-width:200px"><?= htmlspecialchars($book['title']) ?></td>
                    <td><?= htmlspecialchars($book['author']) ?></td>
                    <td><span class="badge-status badge-processing"><?= htmlspecialchars($book['category_name']) ?></span></td>
                    <td class="td-bold">Rp <?= number_format($book['price'], 0, ',', '.') ?></td>
                    <td>
                        <span class="badge-status <?= $book['stock'] == 0 ? 'badge-cancelled' : ($book['stock'] <= 5 ? 'badge-pending' : 'badge-delivered') ?>">
                            <?= $book['stock'] ?>
                        </span>
                    </td>
                    <td style="white-space:nowrap">
                        <a href="<?= BASE_URL ?>admin/books/edit.php?id=<?= $book['id'] ?>" class="btn-action btn-edit">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="<?= BASE_URL ?>admin/books/delete.php?id=<?= $book['id'] ?>"
                           class="btn-action btn-delete ms-1"
                           onclick="return confirm('Hapus buku ini?')">
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