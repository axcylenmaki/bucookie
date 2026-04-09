<?php
include "../config/db.php";

$token = $_GET['token'] ?? '';
$error = "";
$success = "";

// Pastikan nama kolom 'reset_expired' sesuai dengan database kamu
$q = mysqli_query($conn, "
  SELECT * FROM users 
  WHERE reset_token='$token' 
  AND reset_expired > NOW()
");

if (mysqli_num_rows($q) != 1) {
  die("
    <style>
        body { background: #0f172a; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; }
        .box { background: #1e293b; padding: 20px; border-radius: 10px; border: 1px solid #ef4444; text-align: center; }
        a { color: #38bdf8; text-decoration: none; margin-top: 10px; display: block; }
    </style>
    <div class='box'>
        <h3>Link reset tidak valid atau sudah kadaluarsa.</h3>
        <a href='forgot-password.php'>Minta link baru lagi</a>
    </div>
  ");
}

$user = mysqli_fetch_assoc($q);

if (isset($_POST['reset'])) {
  $pass = $_POST['password'];
  $confirm = $_POST['confirm'];

  if ($pass !== $confirm) {
    $error = "Password tidak sama!";
  } else {
    $hash = password_hash($pass, PASSWORD_DEFAULT);

    // Update password dan hapus token agar tidak bisa dipakai lagi
    mysqli_query($conn, "
      UPDATE users SET 
      password='$hash',
      reset_token=NULL,
      reset_expired=NULL
      WHERE id={$user['id']}
    ");

    header("Location: login.php?reset=success");
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | Bookie</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #0f172a 0%, #020617 100%);
      color: #f8fafc;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .card {
      background-color: #1e293b;
      border: 1px solid #334155;
      border-radius: 15px;
    }

    .card-body h4 {
      color: #38bdf8;
    }

    .form-control {
      background-color: #0f172a;
      border: 1px solid #475569;
      color: #f8fafc;
    }

    .form-control:focus {
      background-color: #0f172a;
      color: #f8fafc;
      border-color: #38bdf8;
      box-shadow: 0 0 0 0.25rem rgba(56, 189, 248, 0.25);
    }

    .btn-primary {
      background-color: #0284c7;
      border: none;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      background-color: #0ea5e9;
      transform: translateY(-2px);
    }

    label {
      color: #94a3b8;
      margin-bottom: 5px;
    }

    .alert-danger {
      background-color: #1e293b;
      color: #f87171;
      border: 1px solid #ef4444;
      border-radius: 10px;
    }
  </style>
</head>
<body>

<div class="container">
  <div class="row justify-content-center vh-100 align-items-center">
    <div class="col-md-4">

      <div class="card shadow-lg">
        <div class="card-body p-4">
          <h4 class="text-center fw-bold mb-4">New Password</h4>

          <?php if ($error): ?>
            <div class="alert alert-danger text-center small"><?= $error ?></div>
          <?php endif; ?>

          <form method="POST">
            <div class="mb-3">
              <label>Password Baru</label>
              <input type="password" name="password" class="form-control" placeholder="••••••••" required autofocus>
            </div>

            <div class="mb-3">
              <label>Konfirmasi Password</label>
              <input type="password" name="confirm" class="form-control" placeholder="••••••••" required>
            </div>

            <button name="reset" class="btn btn-primary w-100 py-2 mt-2">
              Update Password
            </button>
          </form>

        </div>
      </div>

    </div>
  </div>
</div>

</body>
</html>