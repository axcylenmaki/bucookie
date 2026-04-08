<?php
/**
 * Bucookie Mailer Helper
 * Kirim email invoice via PHPMailer + SMTP
 *
 * Require: composer require phpmailer/phpmailer
 * Konfigurasi SMTP di bawah sesuaikan dengan provider kamu
 * (Gmail, Mailtrap, SMTP hosting, dll)
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bucookie/vendor/autoload.php';

// ── Konfigurasi SMTP ─────────────────────────────────
// Ganti nilai-nilai ini di file .env atau langsung di sini
define('MAIL_HOST',       getenv('MAIL_HOST')       ?: 'smtp.gmail.com');
define('MAIL_PORT',       getenv('MAIL_PORT')       ?: 587);
define('MAIL_USERNAME',   getenv('MAIL_USERNAME')   ?: 'your_email@gmail.com');
define('MAIL_PASSWORD',   getenv('MAIL_PASSWORD')   ?: 'your_app_password');
define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: 'noreply@bucookie.com');
define('MAIL_FROM_NAME',  getenv('MAIL_FROM_NAME')  ?: 'Bucookie - Toko Buku Online');
// ─────────────────────────────────────────────────────

/**
 * Kirim invoice sebagai attachment PDF ke email user
 *
 * @param array  $order         Data order dari DB
 * @param array  $items         Item pesanan
 * @param string $invoice_html  HTML invoice (dari buildInvoiceHtml)
 * @param string $invoice_no    Nomor invoice, misal '#00012'
 * @return array ['success' => bool, 'message' => string]
 */
function sendInvoiceEmail(array $order, array $items, string $invoice_html, string $invoice_no): array
{
    try {
        // Generate PDF dulu pakai dompdf
        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($invoice_html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf_content = $dompdf->output();

        // Simpan PDF ke temp
        $tmp_pdf = sys_get_temp_dir() . '/invoice_' . $order['id'] . '_' . time() . '.pdf';
        file_put_contents($tmp_pdf, $pdf_content);

        // Setup PHPMailer
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        // From & To
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($order['user_email'], $order['user_name']);
        $mail->addReplyTo(MAIL_FROM_EMAIL, MAIL_FROM_NAME);

        // Attach PDF
        $filename_pdf = 'Invoice_Bucookie_' . str_pad($order['id'], 5, '0', STR_PAD_LEFT) . '.pdf';
        $mail->addAttachment($tmp_pdf, $filename_pdf);

        // Subject
        $mail->Subject = '📚 Invoice Pesanan Bucookie ' . $invoice_no;

        // Body email HTML
        $total    = number_format($order['total_price'], 0, ',', '.');
        $date_str = date('d F Y', strtotime($order['created_at']));

        $items_summary = '';
        foreach ($items as $item) {
            $items_summary .= "<tr>
              <td style='padding:8px 12px; font-size:13px; border-bottom:1px solid #f0f0f0;'>"
                . htmlspecialchars($item['title']) . "<br>
                <span style='font-size:11px; color:#888;'>"
                . htmlspecialchars($item['author']) . "</span>
              </td>
              <td style='padding:8px 12px; text-align:center; font-size:13px; color:#555; border-bottom:1px solid #f0f0f0;'>" . $item['quantity'] . "</td>
              <td style='padding:8px 12px; text-align:right; font-size:13px; font-weight:bold; color:#111; border-bottom:1px solid #f0f0f0;'>Rp "
                . number_format($item['price'] * $item['quantity'], 0, ',', '.') . "</td>
            </tr>";
        }

        $mail->isHTML(true);
        $mail->Body = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='margin:0; padding:0; background:#f1f5f9; font-family: Arial, sans-serif;'>
<div style='max-width:580px; margin:40px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08);'>

  <!-- Header -->
  <div style='background:#3b82f6; padding:32px 40px; text-align:center;'>
    <div style='font-size:28px; color:#fff; font-weight:900; letter-spacing:-0.5px;'>📚 Bucookie</div>
    <div style='color:#bfdbfe; font-size:13px; margin-top:6px;'>Konfirmasi & Invoice Pesanan</div>
  </div>

  <!-- Body -->
  <div style='padding:36px 40px;'>
    <p style='font-size:15px; color:#111; margin:0 0 8px;'>Halo, <strong>" . htmlspecialchars($order['user_name']) . "</strong>! 👋</p>
    <p style='font-size:14px; color:#555; margin:0 0 24px; line-height:1.7;'>
      Terima kasih telah berbelanja di Bucookie. Pesanan kamu dengan nomor
      <strong style='color:#3b82f6;'>{$invoice_no}</strong> telah berhasil diproses pada <strong>{$date_str}</strong>.
    </p>

    <!-- Info box -->
    <div style='background:#f8fafc; border-left:4px solid #3b82f6; border-radius:0 8px 8px 0; padding:16px 20px; margin-bottom:24px;'>
      <div style='font-size:12px; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:10px;'>Ringkasan Pesanan</div>
      <table width='100%' style='border-collapse:collapse;'>
        <thead>
          <tr style='background:#3b82f6; color:#fff;'>
            <th style='padding:8px 12px; font-size:11px; text-align:left; text-transform:uppercase;'>Buku</th>
            <th style='padding:8px 12px; font-size:11px; text-align:center; text-transform:uppercase;'>Qty</th>
            <th style='padding:8px 12px; font-size:11px; text-align:right; text-transform:uppercase;'>Total</th>
          </tr>
        </thead>
        <tbody>{$items_summary}</tbody>
        <tfoot>
          <tr style='background:#eff6ff;'>
            <td colspan='2' style='padding:10px 12px; font-weight:bold; font-size:14px; color:#111;'>Total Pembayaran</td>
            <td style='padding:10px 12px; text-align:right; font-weight:900; font-size:15px; color:#3b82f6;'>Rp {$total}</td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- Alamat -->
    <div style='background:#f8fafc; border-radius:8px; padding:16px 20px; margin-bottom:24px;'>
      <div style='font-size:12px; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px;'>Alamat Pengiriman</div>
      <div style='font-size:13px; color:#555; line-height:1.7;'>" . nl2br(htmlspecialchars($order['shipping_address'])) . "</div>
    </div>

    <!-- Metode bayar -->
    <div style='background:#f0fdf4; border-radius:8px; padding:14px 20px; margin-bottom:24px;'>
      <div style='font-size:12px; color:#888; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;'>Metode Pembayaran</div>
      <div style='font-size:13px; color:#16a34a; font-weight:bold;'>💵 Bayar di Tempat (Cash on Delivery)</div>
    </div>

    <p style='font-size:13px; color:#888; margin:0 0 4px;'>Invoice lengkap sudah kami lampirkan sebagai file PDF di email ini.</p>
    <p style='font-size:13px; color:#888; margin:0;'>Kamu juga bisa cek status pesanan dan download ulang invoice di halaman <strong>Pesanan Saya</strong>.</p>
  </div>

  <!-- Footer -->
  <div style='background:#f8fafc; padding:24px 40px; text-align:center; border-top:1px solid #eee;'>
    <p style='font-size:12px; color:#888; margin:0;'>
      Email ini dikirim otomatis, mohon tidak membalas langsung.<br>
      Butuh bantuan? Hubungi kami di <a href='mailto:admin@bucookie.com' style='color:#3b82f6;'>admin@bucookie.com</a>
    </p>
    <p style='font-size:11px; color:#aaa; margin:8px 0 0;'>© Bucookie — Toko Buku Online Terpercaya</p>
  </div>

</div>
</body>
</html>";

        // Plain text fallback
        $mail->AltBody = "Halo {$order['user_name']}!\n\n"
            . "Pesanan kamu {$invoice_no} telah berhasil diproses.\n"
            . "Total: Rp {$total}\n\n"
            . "Invoice terlampir sebagai PDF.\n\n"
            . "Terima kasih telah berbelanja di Bucookie!";

        $mail->send();

        // Hapus temp file
        if (file_exists($tmp_pdf)) unlink($tmp_pdf);

        return ['success' => true, 'message' => 'Invoice berhasil dikirim ke ' . $order['user_email']];

    } catch (Exception $e) {
        if (isset($tmp_pdf) && file_exists($tmp_pdf)) unlink($tmp_pdf);
        error_log('[Bucookie Mailer] Error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Gagal mengirim email: ' . $e->getMessage()];
    }
}