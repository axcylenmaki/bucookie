<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Bantuan & Kontak';

$contact = $conn->query("SELECT * FROM contact_info LIMIT 1")->fetch_assoc();
$wa      = $contact['whatsapp'] ?? '';
$email   = $contact['email']    ?? '';

$faqs = [
    [
        'q' => 'Bagaimana cara memesan buku?',
        'a' => 'Pilih buku yang kamu inginkan, klik "Tambah ke Keranjang", lalu buka halaman Keranjang dan klik "Checkout Sekarang". Isi alamat pengiriman dan konfirmasi pesanan.'
    ],
    [
        'q' => 'Apakah ada biaya pengiriman?',
        'a' => 'Tidak ada! Semua pengiriman gratis tanpa minimum pembelian.'
    ],
    [
        'q' => 'Bagaimana cara membayar?',
        'a' => 'Bucookie menggunakan sistem Payment at Delivery — kamu bayar tunai saat buku tiba di tanganmu. Tidak perlu transfer dulu.'
    ],
    [
        'q' => 'Berapa lama proses pengiriman?',
        'a' => 'Pesanan akan diproses dalam 1-2 hari kerja. Estimasi pengiriman 2-5 hari kerja tergantung lokasi.'
    ],
    [
        'q' => 'Bagaimana cara cek status pesanan?',
        'a' => 'Login ke akunmu, lalu buka menu "Pesanan Saya". Kamu bisa melihat status terkini dari setiap pesanan di sana.'
    ],
    [
        'q' => 'Bisakah saya membatalkan pesanan?',
        'a' => 'Pembatalan bisa dilakukan selama status pesanan masih "Menunggu". Hubungi kami via WhatsApp atau Email untuk proses pembatalan.'
    ],
    [
        'q' => 'Bagaimana jika buku yang diterima rusak atau salah?',
        'a' => 'Segera hubungi kami dalam 1x24 jam setelah buku diterima melalui WhatsApp dengan menyertakan foto kondisi buku. Kami akan proses penggantian segera.'
    ],
    [
        'q' => 'Apakah saya harus daftar akun untuk membeli?',
        'a' => 'Ya, kamu perlu membuat akun terlebih dahulu agar bisa melakukan pemesanan dan memantau status pesanan. Pendaftaran gratis dan hanya butuh beberapa detik.'
    ],
];

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Hero -->
<div style="text-align:center;padding:32px 0 28px">
    <div style="width:64px;height:64px;background:var(--accent-soft);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px">
        <i class="bi bi-question-circle" style="font-size:1.8rem;color:var(--accent)"></i>
    </div>
    <h1 style="font-family:'Lora',serif;font-size:1.8rem;font-weight:600;margin-bottom:8px">Bantuan & Kontak</h1>
    <p style="font-size:.875rem;color:var(--text-secondary);max-width:440px;margin:0 auto">
        Temukan jawaban dari pertanyaan umum, atau langsung hubungi tim kami.
    </p>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start;max-width:960px;margin:0 auto">

    <!-- FAQ -->
    <div>
        <h2 style="font-size:.78rem;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted);margin-bottom:14px;display:flex;align-items:center;gap:6px">
            <i class="bi bi-chat-left-text" style="color:var(--accent)"></i> Pertanyaan Umum
        </h2>
        <div style="display:flex;flex-direction:column;gap:8px">
            <?php foreach ($faqs as $i => $faq): ?>
            <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;overflow:hidden">
                <button onclick="toggleFaq(<?= $i ?>)"
                        style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:15px 18px;background:none;border:none;cursor:pointer;text-align:left;gap:12px">
                    <span style="font-size:.855rem;font-weight:500;color:var(--text-primary)"><?= htmlspecialchars($faq['q']) ?></span>
                    <i class="bi bi-chevron-down" id="icon-<?= $i ?>"
                       style="color:var(--text-muted);flex-shrink:0;font-size:.8rem;transition:transform .2s"></i>
                </button>
                <div id="faq-<?= $i ?>" style="display:none;padding:0 18px 15px">
                    <div style="height:1px;background:var(--border);margin-bottom:12px"></div>
                    <p style="font-size:.83rem;color:var(--text-secondary);line-height:1.7"><?= htmlspecialchars($faq['a']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Kontak -->
    <div style="position:sticky;top:70px">
        <h2 style="font-size:.78rem;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted);margin-bottom:14px;display:flex;align-items:center;gap:6px">
            <i class="bi bi-headset" style="color:var(--accent)"></i> Hubungi Kami
        </h2>

        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:12px">
            <?php if ($wa): ?>
            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $wa) ?>?text=Halo+Bucookie,+saya+ingin+bertanya..."
               target="_blank"
               style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:16px;text-decoration:none;display:flex;align-items:center;gap:14px;transition:border-color .2s"
               onmouseover="this.style.borderColor='#25D366'" onmouseout="this.style.borderColor='var(--border)'">
                <div style="width:40px;height:40px;border-radius:10px;background:rgba(37,211,102,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-whatsapp" style="font-size:1.2rem;color:#25D366"></i>
                </div>
                <div>
                    <div style="font-size:.83rem;font-weight:600;color:var(--text-primary)">WhatsApp</div>
                    <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px"><?= htmlspecialchars($wa) ?></div>
                </div>
                <i class="bi bi-arrow-right" style="color:var(--text-muted);margin-left:auto;font-size:.8rem"></i>
            </a>
            <?php endif; ?>

            <?php if ($email): ?>
            <a href="mailto:<?= htmlspecialchars($email) ?>?subject=Pertanyaan+dari+Bucookie"
               style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:16px;text-decoration:none;display:flex;align-items:center;gap:14px;transition:border-color .2s"
               onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
                <div style="width:40px;height:40px;border-radius:10px;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-envelope" style="font-size:1.2rem;color:var(--accent)"></i>
                </div>
                <div>
                    <div style="font-size:.83rem;font-weight:600;color:var(--text-primary)">Email</div>
                    <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px"><?= htmlspecialchars($email) ?></div>
                </div>
                <i class="bi bi-arrow-right" style="color:var(--text-muted);margin-left:auto;font-size:.8rem"></i>
            </a>
            <?php endif; ?>

            <?php if (!$wa && !$email): ?>
            <div style="text-align:center;padding:24px;color:var(--text-muted);font-size:.82rem;background:var(--bg-card);border:1px solid var(--border);border-radius:10px">
                <i class="bi bi-exclamation-circle" style="display:block;font-size:1.4rem;margin-bottom:8px"></i>
                Informasi kontak belum diatur.
            </div>
            <?php endif; ?>
        </div>

        <!-- Jam Operasional -->
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:16px">
            <div style="font-size:.75rem;font-weight:600;color:var(--text-secondary);margin-bottom:10px;display:flex;align-items:center;gap:6px">
                <i class="bi bi-clock" style="color:var(--accent)"></i> Jam Operasional
            </div>
            <div style="display:flex;flex-direction:column;gap:7px;font-size:.78rem">
                <div style="display:flex;justify-content:space-between;color:var(--text-muted)">
                    <span>Senin – Jumat</span>
                    <span style="color:var(--text-secondary)">08.00 – 17.00</span>
                </div>
                <div style="display:flex;justify-content:space-between;color:var(--text-muted)">
                    <span>Sabtu</span>
                    <span style="color:var(--text-secondary)">09.00 – 15.00</span>
                </div>
                <div style="display:flex;justify-content:space-between;color:var(--text-muted)">
                    <span>Minggu & Libur</span>
                    <span style="color:#f87171">Tutup</span>
                </div>
            </div>
        </div>

        <!-- Respon time -->
        <div style="margin-top:10px;background:var(--accent-soft);border:1px solid rgba(59,130,246,.15);border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px">
            <i class="bi bi-lightning-charge" style="color:var(--accent);font-size:1rem;flex-shrink:0"></i>
            <p style="font-size:.75rem;color:var(--text-secondary);line-height:1.5">
                Rata-rata waktu respon kami <strong style="color:var(--accent)">&lt; 2 jam</strong> di hari kerja.
            </p>
        </div>
    </div>

</div>

<?php $extra_js = <<<JS
<script>
function toggleFaq(i) {
    const body = document.getElementById('faq-' + i);
    const icon = document.getElementById('icon-' + i);
    const isOpen = body.style.display === 'block';
    body.style.display = isOpen ? 'none' : 'block';
    icon.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
}
</script>
JS; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>