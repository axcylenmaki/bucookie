<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . ($_SESSION['user_role'] === 'admin' ? 'admin/index.php' : 'index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            header('Location: ' . BASE_URL . ($user['role'] === 'admin' ? 'admin/index.php' : 'index.php'));
            exit;
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Bucookie</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bg-base:      #0d1117;
            --bg-card:      #131d2a;
            --border:       rgba(255,255,255,0.06);
            --accent:       #3b82f6;
            --accent-soft:  rgba(59,130,246,0.12);
            --accent-glow:  rgba(59,130,246,0.25);
            --text-primary: #e8edf3;
            --text-secondary:#7a8fa6;
            --text-muted:   #3d5066;
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
            max-width: 420px;
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

        .input-wrap {
            position: relative;
        }

        .input-wrap .toggle-pw {
            position: absolute;
            right: 12px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1rem;
            transition: color .2s;
            background: none;
            border: none;
            padding: 0;
        }

        .input-wrap .toggle-pw:hover { color: var(--text-secondary); }

        .mb-field { margin-bottom: 18px; }

        /* Link Forgot Password */
        .forgot-link-wrap {
            text-align: right;
            margin-top: -12px;
            margin-bottom: 20px;
        }

        .forgot-link-wrap a {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: color .2s;
        }

        .forgot-link-wrap a:hover {
            color: var(--accent);
            text-decoration: underline;
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

        .btn-submit:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

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

        .auth-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .auth-footer a {
            color: var(--accent);
            text-decoration: none;
        }

        .auth-footer a:hover { text-decoration: underline; }

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

        .divider span {
            font-size: 0.72rem;
            color: var(--text-muted);
        }

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
        <h2>Selamat datang</h2>
        <p class="subtitle">Masuk ke akun Bucookie kamu</p>

        <?php if ($error): ?>
        <div class="alert-err">
            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-field">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control"
                       placeholder="contoh@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required autofocus>
            </div>

            <div class="mb-field">
                <label class="form-label">Password</label>
                <div class="input-wrap">
                    <input type="password" name="password" id="passwordInput"
                           class="form-control" placeholder="••••••••" required
                           style="padding-right: 40px">
                    <button type="button" class="toggle-pw" onclick="togglePassword()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="forgot-link-wrap">
                <a href="<?= BASE_URL ?>auth/forgot-password.php">Lupa password?</a>
            </div>

            <button type="submit" class="btn-submit">
                Masuk <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <div class="divider"><span>atau</span></div>

        <a href="<?= BASE_URL ?>index.php" class="btn-back">
            <i class="bi bi-arrow-left"></i> Kembali ke Beranda
        </a>
    </div>

    <div class="auth-footer">
        Belum punya akun? <a href="<?= BASE_URL ?>auth/register.php">Daftar sekarang</a>
    </div>

</div>

<script>
    function togglePassword() {
        const input   = document.getElementById('passwordInput');
        const icon    = document.getElementById('eyeIcon');
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    }
</script>

</body>
</html>