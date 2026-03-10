<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

$id    = (int)($_GET['id'] ?? 0);
$error = '';

$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$cat = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cat) {
    header('Location: ' . BASE_URL . 'admin/categories/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = 'Nama kategori wajib diisi.';
    } else {
        // Cek duplikat (kecuali diri sendiri)
        $check = $conn->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?) AND id != ?");
        $check->bind_param('si', $name, $id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'Nama kategori "' . htmlspecialchars($name) . '" sudah ada.';
        } else {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param('ssi', $name, $desc, $id);
            if ($stmt->execute()) {
                header('Location: ' . BASE_URL . 'admin/categories/index.php?msg=updated');
                exit;
            } else {
                $error = 'Gagal memperbarui, coba lagi.';
            }
            $stmt->close();
        }
        $check->close();
    }
}

$page_title = 'Edit Kategori';
require_once __DIR__ . '/../../admin/includes/header.php';
?>

<div class="page-header">
    <h1>Edit Kategori</h1>
    <p>Perbarui data kategori</p>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card" style="max-width:560px">
    <form method="POST">
        <div class="mb-field">
            <label class="form-label">Nama Kategori <span class="req">*</span></label>
            <input type="text" name="name" class="form-control"
                   value="<?= htmlspecialchars($_POST['name'] ?? $cat['name']) ?>"
                   required autofocus>
        </div>
        <div class="mb-field">
            <label class="form-label">Deskripsi</label>
            <textarea name="description" class="form-control"><?= htmlspecialchars($_POST['description'] ?? $cat['description']) ?></textarea>
        </div>
        <div>
            <button type="submit" class="btn-save">
                <i class="bi bi-check-lg"></i> Simpan Perubahan
            </button>
            <a href="<?= BASE_URL ?>admin/categories/index.php" class="btn-cancel">Batal</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>