<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$error   = '';
$success = '';
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'name'    => trim($_POST['name']    ?? ''),
        'email'   => trim($_POST['email']   ?? ''),
        'phone'   => trim($_POST['phone']   ?? ''),
        'address' => trim($_POST['address'] ?? ''),
    ];
    $password = $_POST['password']         ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    // Validasi
    if (empty($old['name']) || empty($old['email']) || empty($password)) {
        $error = 'Nama, email, dan password wajib diisi.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Cek email duplikat
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $old['email']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Email sudah terdaftar, silakan gunakan email lain.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt->close();

            $stmt = $conn->prepare("
                INSERT INTO users (name, email, password, phone, address)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('sssss',
                $old['name'],
                $old['email'],
                $hashed,
                $old['phone'],
                $old['address']
            );

            if ($stmt->execute()) {
                $success = 'Akun berhasil dibuat! Silakan masuk.';
                $old = [];
            } else {
                $error = 'Terjadi kesalahan, coba lagi.';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — Bucookie</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bg-base:       #0d1117;
            --bg-card:       #131d2a;
            --border:        rgba(255,255,255,0.06);
            --accent:        #3b82f6;
            --accent-soft:   rgba(59,130,246,0.12);
            --accent-glow:   rgba(59,130,246,0.25);
            --text-primary:  #e8edf3;
            --text-secondary:#7a8fa6;
            --text-muted:    #3d5066;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--bg-base);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .auth-wrap {
            width: 100%;
            max-width: 460px;
        }

        .auth-brand {
            text-align: center;
            margin-bottom: 32px;
        }

        .auth-brand .logo-text {
            font-family: 'Lora', serif;
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .auth-brand .logo-text span { color: var(--accent); }

        .auth-brand p {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 4px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .auth-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 36px 32px;
        }

        .auth-card h2 {
            font-family: 'Lora', serif;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .auth-card .subtitle {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 28px;
        }

        .form-label {
            font-size: 0.78rem;
            color: var(--text-secondary);
            margin-bottom: 6px;
            display: block;
        }

        .form-label .req { color: #f87171; margin-left: 2px; }

        .form-control {
            width: 100%;
            background: var(--bg-base);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 14px;
            color: var(--text-primary);
            font-family: 'Sora', sans-serif;
            font-size: 0.875rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .form-control::placeholder { color: var(--text-muted); }

        textarea.form-control { resize: vertical; min-height: 80px; }

        .input-wrap { position: relative; }

        .input-wrap .toggle-pw {
            position: absolute;
            right: 12px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1rem;
            background: none;
            border: none;
            padding: 0;
            transition: color .2s;
        }

        .input-wrap .toggle-pw:hover { color: var(--text-secondary); }

        .mb-field { margin-bottom: 16px; }

        .row-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .hint {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .btn-submit {
            width: 100%;
            padding: 11px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: 'Sora', sans-serif;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background .2s, transform .15s;
            margin-top: 8px;
        }

        .btn-submit:hover { background: #2563eb; transform: translateY(-1px); }
        .btn-submit:active { transform: translateY(0); }

        .alert-err {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #f87171;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.8rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-ok {
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.25);
            color: #4ade80;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.8rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .divider span { font-size: 0.72rem; color: var(--text-muted); }

        .btn-back {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            padding: 10px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-secondary);
            font-family: 'Sora', sans-serif;
            font-size: 0.8rem;
            text-decoration: none;
            transition: all .2s;
        }

        .btn-back:hover {
            background: var(--accent-soft);
            color: var(--text-primary);
            border-color: var(--accent);
        }

        .auth-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .auth-footer a { color: var(--accent); text-decoration: none; }
        .auth-footer a:hover { text-decoration: underline; }

        @media (max-width: 480px) {
            .row-fields { grid-template-columns: 1fr; }
            .auth-card { padding: 28px 20px; }
        }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: var(--bg-base); }
        ::-webkit-scrollbar-thumb { background: var(--text-muted); border-radius: 999px; }
    </style>
</head>
<body>

<div class="auth-wrap">

    <div class="auth-brand">
        <div class="logo-text">Bu<span>cookie</span></div>
        <p>Toko Buku Online</p>
    </div>

    <div class="auth-card">
        <h2>Buat akun baru</h2>
        <p class="subtitle">Daftar dan mulai belanja buku favoritmu</p>

        <?php if ($error): ?>
        <div class="alert-err">
            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert-ok">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
            <a href="<?= BASE_URL ?>auth/login.php" style="color:#4ade80;margin-left:4px;">Masuk sekarang →</a>
        </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="row-fields">
                <div class="mb-field">
                    <label class="form-label">Nama Lengkap <span class="req">*</span></label>
                    <input type="text" name="name" class="form-control"
                           placeholder="John Doe"
                           value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
                </div>
                <div class="mb-field">
                    <label class="form-label">No. WhatsApp</label>
                    <input type="text" name="phone" class="form-control"
                           placeholder="08xxxxxxxxxx"
                           value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-field">
                <label class="form-label">Email <span class="req">*</span></label>
                <input type="email" name="email" class="form-control"
                       placeholder="contoh@email.com"
                       value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
            </div>

            <div class="mb-field">
                <label class="form-label">Alamat Pengiriman</label>
                <textarea name="address" class="form-control"
                          placeholder="Jl. Contoh No. 1, Kota..."><?= htmlspecialchars($old['address'] ?? '') ?></textarea>
            </div>

            <div class="row-fields">
                <div class="mb-field">
                    <label class="form-label">Password <span class="req">*</span></label>
                    <div class="input-wrap">
                        <input type="password" name="password" id="pwInput"
                               class="form-control" placeholder="••••••••"
                               required style="padding-right:40px">
                        <button type="button" class="toggle-pw" onclick="togglePw('pwInput','eyeA')">
                            <i class="bi bi-eye" id="eyeA"></i>
                        </button>
                    </div>
                    <div class="hint">Minimal 6 karakter</div>
                </div>
                <div class="mb-field">
                    <label class="form-label">Konfirmasi Password <span class="req">*</span></label>
                    <div class="input-wrap">
                        <input type="password" name="password_confirm" id="pwInput2"
                               class="form-control" placeholder="••••••••"
                               required style="padding-right:40px">
                        <button type="button" class="toggle-pw" onclick="togglePw('pwInput2','eyeB')">
                            <i class="bi bi-eye" id="eyeB"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                Daftar Sekarang <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <div class="divider"><span>atau</span></div>

        <a href="<?= BASE_URL ?>index.php" class="btn-back">
            <i class="bi bi-arrow-left"></i> Kembali ke Beranda
        </a>
    </div>

    <div class="auth-footer">
        Sudah punya akun? <a href="<?= BASE_URL ?>auth/login.php">Masuk di sini</a>
    </div>

</div>

<script>
    function togglePw(inputId, iconId) {
        const input  = document.getElementById(inputId);
        const icon   = document.getElementById(iconId);
        const hidden = input.type === 'password';
        input.type   = hidden ? 'text' : 'password';
        icon.className = hidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    }
</script>

</body>
</html>