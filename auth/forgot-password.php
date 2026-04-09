<?php
session_start();
include "../config/db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "../vendor/autoload.php";

$msg = "";
$error = "";

if (isset($_POST['submit'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Query sesuai database kamu (menggunakan 'name')
    $q = mysqli_query($conn, "SELECT id, name, email FROM users WHERE email='$email' LIMIT 1");

    if (mysqli_num_rows($q) !== 1) {
        $error = "Email tidak terdaftar!";
    } else {
        $user = mysqli_fetch_assoc($q);
        $token = bin2hex(random_bytes(32));

        // Update token & expiry
        mysqli_query($conn, "UPDATE users SET reset_token='$token', reset_expired = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id={$user['id']}");

        $link = "http://localhost/bucookie/auth/reset-password.php?token=$token";
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'ayusyafira3003@gmail.com';
            $mail->Password   = 'dmvo fsfe gdss pkae';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('ayusyafira3003@gmail.com', 'Bookie');
            $mail->addAddress($user['email'], $user['name']);

            $mail->isHTML(true);
            $mail->Subject = 'Reset Password Bookie';
            $mail->Body    = "Halo <b>{$user['name']}</b>,<br><br>Klik link untuk reset password:<br><a href='$link'>$link</a>";

            $mail->send();
            $msg = "Link reset password telah dikirim ke email!";
        } catch (Exception $e) {
            $error = "Gagal kirim email: {$mail->ErrorInfo}";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Bookie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            /* Warna Hitam Pekat ke Biru Dongker */
            background: linear-gradient(135deg, #0f172a 0%, #020617 100%);
            color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            background-color: #1e293b; /* Biru Dongker Gelap */
            border: 1px solid #334155;
            border-radius: 15px;
        }

        .card-body h4 {
            color: #38bdf8; /* Biru Cerah untuk Heading */
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
            box-shadow: 0 0 0 0.25 cold-res-blue;
            outline: 0;
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

        a {
            color: #38bdf8;
            text-decoration: none;
        }

        a:hover {
            color: #7dd3fc;
            text-decoration: underline;
        }

        .alert {
            border-radius: 10px;
            background-color: #1e293b;
            border: 1px solid;
        }
        
        .alert-danger { color: #f87171; border-color: #ef4444; }
        .alert-success { color: #4ade80; border-color: #22c55e; }

        hr {
            border-top: 1px solid #475569;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center align-items-center vh-100">
        <div class="col-md-4">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <h4 class="text-center fw-bold mb-4">Forgot Password</h4>

                    <?php if ($error): ?>
                        <div class="alert alert-danger text-center small"><?= $error ?></div>
                    <?php endif; ?>

                    <?php if ($msg): ?>
                        <div class="alert alert-success text-center small"><?= $msg ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="nama@email.com" required autofocus>
                        </div>

                        <button name="submit" class="btn btn-primary w-100 py-2 mt-2">
                            Kirim Link Reset
                        </button>
                    </form>

                    <hr class="my-4">
                    <div class="text-center">
                        <small>Ingat password? <a href="login.php">Kembali ke Login</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>