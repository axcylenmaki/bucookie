<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireUser();

$error   = '';
$success = '';

// Handle hapus avatar
if (isset($_GET['remove_avatar'])) {
    $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!empty($row['avatar'])) {
        $file = __DIR__ . '/../assets/uploads/avatars/' . $row['avatar'];
        if (file_exists($file)) unlink($file);
    }
    $stmt = $conn->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    $_SESSION['user_avatar'] = null;
    header('Location: ' . BASE_URL . 'user/profile.php?tab=info');
    exit;
}



$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = $_POST['tab'] ?? 'info';

    // ── Tab Info ──
    if ($tab === 'info') {
        $name    = trim($_POST['name']    ?? '');
        $phone   = trim($_POST['phone']   ?? '');
        $address = trim($_POST['address'] ?? '');
        $avatar  = $user['avatar'];

        if (empty($name)) {
            $error = 'Nama tidak boleh kosong.';
        } else {
            // Upload avatar jika ada
            if (!empty($_FILES['avatar']['name'])) {
                $allowed = ['jpg','jpeg','png','webp'];
                $ext     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    $error = 'Format foto harus JPG, PNG, atau WEBP.';
                } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                    $error = 'Ukuran foto maksimal 2MB.';
                } else {
                    // Hapus avatar lama
                    if (!empty($user['avatar'])) {
                        $old = __DIR__ . '/../assets/uploads/avatars/' . $user['avatar'];
                        if (file_exists($old)) unlink($old);
                    }
                    $avatar    = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
                    $uploadDir = __DIR__ . '/../assets/uploads/avatars/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $avatar);
                }
            }

            if (!$error) {
                $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=?, avatar=? WHERE id=?");
                $stmt->bind_param('ssssi', $name, $phone, $address, $avatar, $_SESSION['user_id']);
                $stmt->execute();
                $stmt->close();

                $_SESSION['user_name']   = $name;
                $_SESSION['user_avatar'] = $avatar;
                $user['name']    = $name;
                $user['phone']   = $phone;
                $user['address'] = $address;
                $user['avatar']  = $avatar;
                $success = 'Profil berhasil diperbarui.';
            }
        }

    // ── Tab Password ──
    } elseif ($tab === 'password') {
        $old = $_POST['old_password']     ?? '';
        $new = $_POST['new_password']     ?? '';
        $cfm = $_POST['confirm_password'] ?? '';

        if (!password_verify($old, $user['password'])) {
            $error = 'Password lama tidak sesuai.';
        } elseif (strlen($new) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } elseif ($new !== $cfm) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param('si', $hash, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            $success = 'Password berhasil diubah.';
        }
    }
}

$active_tab = $_GET['tab'] ?? 'info';

// Stats
$stats = $conn->query("
    SELECT COUNT(*) AS total,
           COALESCE(SUM(total_price),0) AS total_spent,
           SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) AS delivered
    FROM orders WHERE user_id = {$_SESSION['user_id']}
")->fetch_assoc();

// Helper avatar
$avatarUrl = !empty($user['avatar']) && file_exists(__DIR__ . '/../assets/uploads/avatars/' . $user['avatar'])
    ? BASE_URL . 'assets/uploads/avatars/' . htmlspecialchars($user['avatar'])
    : null;

$page_title = 'Profil Saya';
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom:20px">
    <h2 style="font-family:'Lora',serif;font-size:1.3rem;font-weight:600;margin-bottom:4px">Profil Saya</h2>
    <p style="font-size:.82rem;color:var(--text-secondary)">Kelola informasi akun kamu</p>
</div>

<!-- Stats Cards -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:16px;text-align:center">
        <div style="font-size:1.4rem;font-weight:700;color:var(--accent)"><?= $stats['total'] ?? 0 ?></div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px">Total Pesanan</div>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:16px;text-align:center">
        <div style="font-size:1.4rem;font-weight:700;color:#4ade80"><?= $stats['delivered'] ?? 0 ?></div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px">Terkirim</div>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:16px;text-align:center">
        <div style="font-size:1rem;font-weight:700;color:var(--text-primary)">Rp <?= number_format($stats['total_spent'] ?? 0, 0, ',', '.') ?></div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px">Total Belanja</div>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- Tabs -->
<div style="display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:24px">
    <a href="?tab=info" style="padding:10px 20px;text-decoration:none;font-size:.83rem;font-weight:500;color:<?= $active_tab==='info' ? 'var(--accent)' : 'var(--text-muted)' ?>;border-bottom:2px solid <?= $active_tab==='info' ? 'var(--accent)' : 'transparent' ?>;margin-bottom:-1px">
        <i class="bi bi-person"></i> Informasi
    </a>
    <a href="?tab=password" style="padding:10px 20px;text-decoration:none;font-size:.83rem;font-weight:500;color:<?= $active_tab==='password' ? 'var(--accent)' : 'var(--text-muted)' ?>;border-bottom:2px solid <?= $active_tab==='password' ? 'var(--accent)' : 'transparent' ?>;margin-bottom:-1px">
        <i class="bi bi-lock"></i> Password
    </a>
</div>

<?php if ($active_tab === 'info'): ?>
<div style="max-width:520px">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="tab" value="info">

        <!-- Avatar Upload -->
        <div class="mb-field">
            <label class="form-label">Foto Profil</label>
            <div style="display:flex;align-items:center;gap:20px">

                <!-- Preview avatar -->
                <div id="avatarPreviewWrap"
                     style="width:80px;height:80px;border-radius:50%;overflow:hidden;flex-shrink:0;border:2px solid var(--border);background:var(--bg-base);display:flex;align-items:center;justify-content:center;position:relative">
                    <?php if ($avatarUrl): ?>
                    <img id="avatarPreview" src="<?= $avatarUrl ?>"
                         style="width:100%;height:100%;object-fit:cover">
                    <?php else: ?>
                    <div id="avatarInitial"
                         style="width:100%;height:100%;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:700;color:var(--accent)">
                        <?= strtoupper(substr($user['name'], 0, 2)) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="flex:1">
                    <label for="avatarInput"
                           style="display:inline-flex;align-items:center;gap:7px;padding:8px 16px;background:var(--bg-card);border:1px solid var(--border);border-radius:8px;cursor:pointer;font-size:.8rem;color:var(--text-secondary);transition:all .15s">
                        <i class="bi bi-camera"></i> Pilih Foto
                    </label>
                    <input type="file" id="avatarInput" name="avatar"
                           accept=".jpg,.jpeg,.png,.webp"
                           style="display:none"
                           onchange="previewAvatar(this)">
                    <div style="font-size:.7rem;color:var(--text-muted);margin-top:6px">JPG, PNG, WEBP. Maks 2MB.</div>
                    <?php if ($avatarUrl): ?>
                    <a href="?tab=info&remove_avatar=1"
                       onclick="return confirm('Hapus foto profil?')"
                       style="font-size:.7rem;color:var(--danger);text-decoration:none;display:inline-flex;align-items:center;gap:4px;margin-top:4px">
                        <i class="bi bi-trash"></i> Hapus foto
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mb-field">
            <label class="form-label">Nama Lengkap <span class="req">*</span></label>
            <input type="text" name="name" class="form-control"
                   value="<?= htmlspecialchars($_POST['name'] ?? $user['name']) ?>" required>
        </div>
        <div class="mb-field">
            <label class="form-label">Email</label>
            <input type="email" class="form-control"
                   value="<?= htmlspecialchars($user['email']) ?>"
                   disabled style="opacity:.5;cursor:not-allowed">
            <div style="font-size:.7rem;color:var(--text-muted);margin-top:4px">Email tidak bisa diubah</div>
        </div>
        <div class="mb-field">
            <label class="form-label">No. HP</label>
            <input type="text" name="phone" class="form-control"
                   placeholder="contoh: 08123456789"
                   value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone'] ?? '') ?>">
        </div>
        <div class="mb-field">
            <label class="form-label">Alamat</label>
            <textarea name="address" class="form-control"
                      placeholder="Alamat lengkap kamu..."><?= htmlspecialchars($_POST['address'] ?? $user['address'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn-save"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
    </form>
</div>

<?php else: ?>
<!-- Tab Password -->
<div style="max-width:500px">
    <form method="POST">
        <input type="hidden" name="tab" value="password">
        <div class="mb-field">
            <label class="form-label">Password Lama <span class="req">*</span></label>
            <input type="password" name="old_password" class="form-control" required>
        </div>
        <div class="mb-field">
            <label class="form-label">Password Baru <span class="req">*</span></label>
            <input type="password" name="new_password" class="form-control" minlength="6" required>
            <div style="font-size:.7rem;color:var(--text-muted);margin-top:4px">Minimal 6 karakter</div>
        </div>
        <div class="mb-field">
            <label class="form-label">Konfirmasi Password Baru <span class="req">*</span></label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn-save"><i class="bi bi-lock"></i> Ubah Password</button>
    </form>
</div>
<?php endif; ?>

<?php
$extra_js = '<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const wrap = document.getElementById("avatarPreviewWrap");
            wrap.innerHTML = "<img id=\"avatarPreview\" src=\"" + e.target.result + "\" style=\"width:100%;height:100%;object-fit:cover\">";
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>';
?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>