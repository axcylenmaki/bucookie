<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

$error      = '';
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $author      = trim($_POST['author']      ?? '');
    $publisher   = trim($_POST['publisher']   ?? '');
    $year        = trim($_POST['year']        ?? '');
    $cost_price  = trim($_POST['cost_price']  ?? 0); // Ambil harga modal
    $price       = trim($_POST['price']       ?? 0); // Ini harga jual
    $stock       = (int)($_POST['stock']      ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description']  ?? '');
    $cover       = null;

    // Validasi tambahan untuk harga modal
    if (empty($title) || empty($author) || empty($price) || empty($cost_price) || $category_id === 0) {
        $error = 'Judul, penulis, harga modal, harga jual, dan kategori wajib diisi.';
    } else {
        // --- Bagian Upload Cover (Tetap Sama) ---
        if (!empty($_FILES['cover']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext     = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Format cover harus JPG, PNG, atau WEBP.';
            } elseif ($_FILES['cover']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran cover maksimal 2MB.';
            } else {
                $cover     = uniqid('cover_') . '.' . $ext;
                $uploadDir = __DIR__ . '/../../assets/uploads/covers/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                move_uploaded_file($_FILES['cover']['tmp_name'], $uploadDir . $cover);
            }
        }

        if (!$error) {
            // Update Query: Tambahkan cost_price
            $stmt = $conn->prepare("
                INSERT INTO books (category_id, title, author, publisher, year, cost_price, price, stock, description, cover)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            // Binding param: 'issssddiss' (d untuk double/decimal)
            $stmt->bind_param('issssddiss', $category_id, $title, $author, $publisher, $year, $cost_price, $price, $stock, $description, $cover);
            
            if ($stmt->execute()) {
                header('Location: ' . BASE_URL . 'admin/books/index.php?msg=added');
                exit;
            } else {
                $error = 'Gagal menyimpan, coba lagi.';
            }
            $stmt->close();
        }
    }
}

$page_title = 'Tambah Buku';
require_once __DIR__ . '/../../admin/includes/header.php';
?>

<div class="page-header">
    <h1>Tambah Buku</h1>
    <p>Tambahkan buku baru ke katalog</p>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card" style="max-width:700px">
    <form method="POST" enctype="multipart/form-data">

        <div class="mb-field">
            <label class="form-label">Judul Buku <span class="req">*</span></label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required autofocus>
        </div>

        <div class="form-grid-2">
            <div class="mb-field">
                <label class="form-label">Penulis <span class="req">*</span></label>
                <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($_POST['author'] ?? '') ?>" required>
            </div>
            <div class="mb-field">
                <label class="form-label">Penerbit</label>
                <input type="text" name="publisher" class="form-control" value="<?= htmlspecialchars($_POST['publisher'] ?? '') ?>">
            </div>
        </div>

        <div class="form-grid-3">
            <div class="mb-field">
                <label class="form-label">Harga Modal (Rp) <span class="req">*</span></label>
                <input type="number" id="cost_price" name="cost_price" class="form-control" 
                       value="<?= htmlspecialchars($_POST['cost_price'] ?? '') ?>" required oninput="calculateProfit()">
            </div>
            <div class="mb-field">
                <label class="form-label">Harga Jual (Rp) <span class="req">*</span></label>
                <input type="number" id="price" name="price" class="form-control" 
                       value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required oninput="calculateProfit()">
            </div>
            <div class="mb-field">
                <label class="form-label">Keuntungan/pcs</label>
                <input type="text" id="profit_display" class="form-control" 
                       style="background: #e9ecef; font-weight: bold; color: #28a745;" readonly placeholder="Rp 0">
            </div>
        </div>

        <div class="form-grid-3">
            <div class="mb-field">
                <label class="form-label">Kategori <span class="req">*</span></label>
                <select name="category_id" class="form-select" required>
                    <option value="">-- Pilih --</option>
                    <?php 
                    $categories->data_seek(0);
                    while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-field">
                <label class="form-label">Tahun Terbit</label>
                <input type="number" name="year" class="form-control" min="1900" max="<?= date('Y') ?>" value="<?= htmlspecialchars($_POST['year'] ?? date('Y')) ?>">
            </div>
            <div class="mb-field">
                <label class="form-label">Stok <span class="req">*</span></label>
                <input type="number" name="stock" class="form-control" min="0" value="<?= htmlspecialchars($_POST['stock'] ?? '0') ?>" required>
            </div>
        </div>

        <div class="mb-field">
            <label class="form-label">Deskripsi</label>
            <textarea name="description" class="form-control"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="mb-field">
            <label class="form-label">Cover Buku</label>
            <input type="file" name="cover" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div>
            <button type="submit" class="btn-save"><i class="bi bi-check-lg"></i> Simpan</button>
            <a href="<?= BASE_URL ?>admin/books/index.php" class="btn-cancel">Batal</a>
        </div>
    </form>
</div>

<script>
function calculateProfit() {
    const cost = parseFloat(document.getElementById('cost_price').value) || 0;
    const price = parseFloat(document.getElementById('price').value) || 0;
    const profit = price - cost;
    
    const display = document.getElementById('profit_display');
    
    // Format ke rupiah
    display.value = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(profit);

    // Ubah warna jika rugi
    if(profit < 0) {
        display.style.color = '#dc3545';
    } else {
        display.style.color = '#28a745';
    }
}
// Jalankan fungsi sekali saat load jika ada nilai lama (old input)
window.onload = calculateProfit;
</script>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>