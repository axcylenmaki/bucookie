<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = 'Nama kategori wajib diisi.';
    } else {
        // Cek duplikat
        $check = $conn->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?)");
        $check->bind_param('s', $name);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'Nama kategori "' . htmlspecialchars($name) . '" sudah ada.';
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param('ss', $name, $desc);
            if ($stmt->execute()) {
                header('Location: ' . BASE_URL . 'admin/categories/index.php?msg=added');
                exit;
            } else {
                $error = 'Gagal menyimpan, coba lagi.';
            }
            $stmt->close();
        }
        $check->close();
    }
}

$page_title = 'Tambah Kategori';
require_once __DIR__ . '/../../admin/includes/header.php';
?>

<div class="page-header">
    <h1>Tambah Kategori</h1>
    <p>Buat kategori baru untuk pengelompokan buku</p>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card" style="max-width:560px">
    <form method="POST">
        <div class="mb-field">
            <label class="form-label">Nama Kategori <span class="req">*</span></label>
            <input type="text" name="name" class="form-control"
                   placeholder="contoh: Novel, Teknologi..."
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                   required autofocus>
        </div>
        <div class="mb-field">
            <label class="form-label">Deskripsi</label>
            <textarea name="description" class="form-control"
                      placeholder="Deskripsi singkat kategori ini..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>
        <div>
            <button type="submit" class="btn-save">
                <i class="bi bi-check-lg"></i> Simpan
            </button>
            <a href="<?= BASE_URL ?>admin/categories/index.php" class="btn-cancel">Batal</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>