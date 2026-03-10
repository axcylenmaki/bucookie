<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

$id    = (int)($_GET['id'] ?? 0);
$error = '';

$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    header('Location: ' . BASE_URL . 'admin/books/index.php');
    exit;
}

$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = trim($_POST['title']        ?? '');
    $author       = trim($_POST['author']       ?? '');
    $publisher    = trim($_POST['publisher']    ?? '');
    $year         = trim($_POST['year']         ?? '');
    $price        = trim($_POST['price']        ?? '');
    $stock_input  = (int)($_POST['stock']       ?? 0);
    $stock_mode   = $_POST['stock_mode']        ?? 'replace'; // 'replace' atau 'add'
    $category_id  = (int)($_POST['category_id'] ?? 0);
    $description  = trim($_POST['description']  ?? '');
    $cover        = $book['cover'];

    // Hitung stok final
    if ($stock_mode === 'add') {
        $final_stock = $book['stock'] + $stock_input;
    } else {
        $final_stock = $stock_input;
    }

    if ($final_stock < 0) $final_stock = 0;

    if (empty($title) || empty($author) || empty($price) || $category_id === 0) {
        $error = 'Judul, penulis, harga, dan kategori wajib diisi.';
    } else {
        // Upload cover baru jika ada
        if (!empty($_FILES['cover']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext     = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Format cover harus JPG, PNG, atau WEBP.';
            } elseif ($_FILES['cover']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran cover maksimal 2MB.';
            } else {
                if (!empty($book['cover'])) {
                    $oldFile = __DIR__ . '/../../assets/uploads/covers/' . $book['cover'];
                    if (file_exists($oldFile)) unlink($oldFile);
                }
                $cover     = uniqid('cover_') . '.' . $ext;
                $uploadDir = __DIR__ . '/../../assets/uploads/covers/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                move_uploaded_file($_FILES['cover']['tmp_name'], $uploadDir . $cover);
            }
        }

        if (!$error) {
            $stmt = $conn->prepare("
                UPDATE books
                SET category_id=?, title=?, author=?, publisher=?, year=?,
                    price=?, stock=?, description=?, cover=?
                WHERE id=?
            ");
            $stmt->bind_param('issssidssi', $category_id, $title, $author, $publisher, $year, $price, $final_stock, $description, $cover, $id);
            if ($stmt->execute()) {
                header('Location: ' . BASE_URL . 'admin/books/index.php?msg=updated');
                exit;
            } else {
                $error = 'Gagal memperbarui, coba lagi.';
            }
            $stmt->close();
        }
    }
}

$page_title = 'Edit Buku';
require_once __DIR__ . '/../../admin/includes/header.php';
?>

<div class="page-header">
    <h1>Edit Buku</h1>
    <p>Perbarui data buku</p>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card" style="max-width:700px">
    <form method="POST" enctype="multipart/form-data">

        <div class="mb-field">
            <label class="form-label">Judul Buku <span class="req">*</span></label>
            <input type="text" name="title" class="form-control"
                   value="<?= htmlspecialchars($_POST['title'] ?? $book['title']) ?>" required autofocus>
        </div>

        <div class="form-grid-2">
            <div class="mb-field">
                <label class="form-label">Penulis <span class="req">*</span></label>
                <input type="text" name="author" class="form-control"
                       value="<?= htmlspecialchars($_POST['author'] ?? $book['author']) ?>" required>
            </div>
            <div class="mb-field">
                <label class="form-label">Penerbit</label>
                <input type="text" name="publisher" class="form-control"
                       value="<?= htmlspecialchars($_POST['publisher'] ?? $book['publisher']) ?>">
            </div>
        </div>

        <div class="form-grid-3">
            <div class="mb-field">
                <label class="form-label">Kategori <span class="req">*</span></label>
                <select name="category_id" class="form-select" required>
                    <option value="">-- Pilih --</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= $cat['id'] ?>"
                        <?= ($_POST['category_id'] ?? $book['category_id']) == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-field">
                <label class="form-label">Tahun Terbit</label>
                <input type="number" name="year" class="form-control"
                       min="1900" max="<?= date('Y') ?>"
                       value="<?= htmlspecialchars($_POST['year'] ?? $book['year']) ?>">
            </div>
            <div class="mb-field">
                <label class="form-label">Harga (Rp) <span class="req">*</span></label>
                <input type="number" name="price" class="form-control" min="0" step="500"
                       value="<?= htmlspecialchars($_POST['price'] ?? $book['price']) ?>" required>
            </div>
        </div>

        <!-- STOK dengan mode pilihan -->
        <div class="mb-field">
            <label class="form-label">Stok <span class="req">*</span></label>

            <!-- Info stok saat ini -->
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
                <span style="font-size:.75rem;color:var(--text-muted)">Stok saat ini:</span>
                <span id="currentStockBadge"
                      style="background:<?= $book['stock'] == 0 ? 'var(--danger-soft)' : ($book['stock'] <= 5 ? 'var(--warning-soft)' : 'var(--success-soft)') ?>;
                             color:<?= $book['stock'] == 0 ? 'var(--danger)' : ($book['stock'] <= 5 ? 'var(--warning)' : 'var(--success)') ?>;
                             padding:2px 10px;border-radius:999px;font-size:.75rem;font-weight:600">
                    <?= $book['stock'] ?> unit
                </span>
            </div>

            <!-- Mode toggle -->
            <div style="display:flex;gap:0;margin-bottom:10px;border:1px solid var(--border);border-radius:8px;overflow:hidden;width:fit-content">
                <label style="cursor:pointer">
                    <input type="radio" name="stock_mode" value="replace"
                           <?= ($_POST['stock_mode'] ?? 'replace') === 'replace' ? 'checked' : '' ?>
                           onchange="updateStockPreview()"
                           style="display:none">
                    <div class="stock-mode-btn" id="btn-replace"
                         style="padding:7px 18px;font-size:.78rem;font-weight:500;transition:all .15s;
                                background:<?= ($_POST['stock_mode'] ?? 'replace') === 'replace' ? 'var(--accent)' : 'var(--bg-base)' ?>;
                                color:<?= ($_POST['stock_mode'] ?? 'replace') === 'replace' ? '#fff' : 'var(--text-muted)' ?>">
                        <i class="bi bi-arrow-repeat"></i> Ganti Stok
                    </div>
                </label>
                <label style="cursor:pointer;border-left:1px solid var(--border)">
                    <input type="radio" name="stock_mode" value="add"
                           <?= ($_POST['stock_mode'] ?? '') === 'add' ? 'checked' : '' ?>
                           onchange="updateStockPreview()"
                           style="display:none">
                    <div class="stock-mode-btn" id="btn-add"
                         style="padding:7px 18px;font-size:.78rem;font-weight:500;transition:all .15s;
                                background:<?= ($_POST['stock_mode'] ?? '') === 'add' ? 'var(--accent)' : 'var(--bg-base)' ?>;
                                color:<?= ($_POST['stock_mode'] ?? '') === 'add' ? '#fff' : 'var(--text-muted)' ?>">
                        <i class="bi bi-plus-circle"></i> Tambah Stok
                    </div>
                </label>
            </div>

            <!-- Input stok -->
            <input type="number" name="stock" id="stockInput" class="form-control"
                   min="0" style="max-width:200px"
                   value="<?= htmlspecialchars($_POST['stock'] ?? $book['stock']) ?>"
                   oninput="updateStockPreview()" required>

            <!-- Preview hasil -->
            <div id="stockPreview" style="margin-top:8px;font-size:.78rem;color:var(--text-muted)">
                <!-- diisi JS -->
            </div>
        </div>

        <div class="mb-field">
            <label class="form-label">Deskripsi</label>
            <textarea name="description" class="form-control"><?= htmlspecialchars($_POST['description'] ?? $book['description']) ?></textarea>
        </div>

        <div class="mb-field">
            <label class="form-label">Cover Buku</label>
            <?php if (!empty($book['cover']) && file_exists(__DIR__ . '/../../assets/uploads/covers/' . $book['cover'])): ?>
            <div style="margin-bottom:10px">
                <img src="<?= BASE_URL ?>assets/uploads/covers/<?= htmlspecialchars($book['cover']) ?>"
                     style="height:80px;border-radius:6px;border:1px solid var(--border)">
                <div style="font-size:.7rem;color:var(--text-muted);margin-top:4px">Cover saat ini</div>
            </div>
            <?php endif; ?>
            <input type="file" name="cover" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            <div style="font-size:.7rem;color:var(--text-muted);margin-top:4px">Kosongkan jika tidak ingin mengubah cover. Format: JPG, PNG, WEBP. Maks 2MB.</div>
        </div>

        <div>
            <button type="submit" class="btn-save"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
            <a href="<?= BASE_URL ?>admin/books/index.php" class="btn-cancel">Batal</a>
        </div>

    </form>
</div>

<?php
$current_stock_js = (int)$book['stock'];
$extra_js = '<script>
const currentStock = ' . $current_stock_js . ';

function updateStockPreview() {
    const mode       = document.querySelector("input[name=\'stock_mode\']:checked").value;
    const input      = parseInt(document.getElementById("stockInput").value) || 0;
    const preview    = document.getElementById("stockPreview");
    const btnReplace = document.getElementById("btn-replace");
    const btnAdd     = document.getElementById("btn-add");

    if (mode === "replace") {
        btnReplace.style.background = "var(--accent)";
        btnReplace.style.color      = "#fff";
        btnAdd.style.background     = "var(--bg-base)";
        btnAdd.style.color          = "var(--text-muted)";
    } else {
        btnAdd.style.background     = "var(--accent)";
        btnAdd.style.color          = "#fff";
        btnReplace.style.background = "var(--bg-base)";
        btnReplace.style.color      = "var(--text-muted)";
    }

    let result, desc;
    if (mode === "replace") {
        result = input;
        desc   = "Stok lama <b>" + currentStock + "</b> akan diganti menjadi <b>" + result + "</b>";
    } else {
        result = currentStock + input;
        desc   = "Stok lama <b>" + currentStock + "</b> + tambahan <b>" + input + "</b> = <b>" + result + "</b>";
    }

    const color = result === 0 ? "var(--danger)" : (result <= 5 ? "var(--warning)" : "var(--success)");
    preview.innerHTML = "<span style=\"color:var(--text-muted)\">" + desc + "</span>"
        + " &nbsp;→&nbsp; <span style=\"color:" + color + ";font-weight:600\">" + result + " unit</span>";
}

updateStockPreview();
</script>';
?>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>