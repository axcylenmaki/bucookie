<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Hubungi Kami';

$contact = $conn->query("SELECT * FROM contact_info LIMIT 1")->fetch_assoc();
$wa      = $contact['whatsapp'] ?? '';
$email   = $contact['email']    ?? '';

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width:600px;margin:0 auto">

    <div style="text-align:center;padding:40px 0 32px">
        <div style="width:72px;height:72px;background:var(--accent-soft);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
            <i class="bi bi-headset" style="font-size:2rem;color:var(--accent)"></i>
        </div>
        <h1 style="font-family:'Lora',serif;font-size:2rem;font-weight:600;margin-bottom:12px">Hubungi Kami</h1>
        <p style="font-size:.875rem;color:var(--text-secondary)">
            Ada pertanyaan atau kendala? Tim kami siap membantu kamu.
        </p>
    </div>

    <div style="display:flex;flex-direction:column;gap:14px">

        <?php if ($wa): ?>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $wa) ?>?text=Halo+Bucookie,+saya+ingin+bertanya..."
           target="_blank"
           style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:22px 24px;text-decoration:none;display:flex;align-items:center;gap:18px;transition:border-color .2s"
           onmouseover="this.style.borderColor='#25D366'" onmouseout="this.style.borderColor='var(--border)'">
            <div style="width:48px;height:48px;border-radius:12px;background:rgba(37,211,102,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="bi bi-whatsapp" style="font-size:1.4rem;color:#25D366"></i>
            </div>
            <div style="flex:1">
                <div style="font-size:.88rem;font-weight:600;color:var(--text-primary);margin-bottom:3px">WhatsApp</div>
                <div style="font-size:.8rem;color:var(--text-muted)"><?= htmlspecialchars($wa) ?></div>
            </div>
            <i class="bi bi-arrow-right" style="color:var(--text-muted)"></i>
        </a>
        <?php endif; ?>

        <?php if ($email): ?>
        <a href="mailto:<?= htmlspecialchars($email) ?>?subject=Pertanyaan+dari+Bucookie"
           style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:22px 24px;text-decoration:none;display:flex;align-items:center;gap:18px;transition:border-color .2s"
           onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
            <div style="width:48px;height:48px;border-radius:12px;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="bi bi-envelope" style="font-size:1.4rem;color:var(--accent)"></i>
            </div>
            <div style="flex:1">
                <div style="font-size:.88rem;font-weight:600;color:var(--text-primary);margin-bottom:3px">Email</div>
                <div style="font-size:.8rem;color:var(--text-muted)"><?= htmlspecialchars($email) ?></div>
            </div>
            <i class="bi bi-arrow-right" style="color:var(--text-muted)"></i>
        </a>
        <?php endif; ?>

        <?php if (!$wa && !$email): ?>
        <div style="text-align:center;padding:40px;color:var(--text-muted);font-size:.85rem">
            <i class="bi bi-exclamation-circle" style="font-size:1.5rem;display:block;margin-bottom:10px"></i>
            Informasi kontak belum tersedia. Silakan hubungi admin.
        </div>
        <?php endif; ?>

    </div>

    <!-- Info jam -->
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:20px;margin-top:14px">
        <h3 style="font-size:.85rem;font-weight:600;margin-bottom:12px;color:var(--text-secondary);display:flex;align-items:center;gap:6px">
            <i class="bi bi-clock" style="color:var(--accent)"></i> Jam Operasional
        </h3>
        <div style="display:flex;flex-direction:column;gap:6px;font-size:.82rem;color:var(--text-muted)">
            <div style="display:flex;justify-content:space-between">
                <span>Senin – Jumat</span>
                <span style="color:var(--text-secondary)">08.00 – 17.00 WIB</span>
            </div>
            <div style="display:flex;justify-content:space-between">
                <span>Sabtu</span>
                <span style="color:var(--text-secondary)">09.00 – 15.00 WIB</span>
            </div>
            <div style="display:flex;justify-content:space-between">
                <span>Minggu & Hari Libur</span>
                <span style="color:#f87171">Tutup</span>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>