<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireUser();

$page_title = 'Chat dengan Admin';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="margin-bottom:16px">
    <h2 style="font-family:'Lora',serif;font-size:1.3rem;font-weight:600;margin-bottom:4px">Chat dengan Admin</h2>
    <p style="font-size:.82rem;color:var(--text-secondary)">Tanya apapun tentang pesanan atau produk kami</p>
</div>

<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;display:flex;flex-direction:column;height:calc(100vh - 240px);min-height:480px">
    <!-- Header -->
    <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px">
        <div style="width:38px;height:38px;border-radius:50%;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;font-size:.8rem;color:var(--accent)">
            <i class="bi bi-shield-check"></i>
        </div>
        <div>
            <div style="font-weight:600;font-size:.88rem;color:var(--text-primary)">Admin Bucookie</div>
            <div style="font-size:.7rem;color:#4ade80;display:flex;align-items:center;gap:4px">
                <span style="width:6px;height:6px;border-radius:50%;background:#4ade80;display:inline-block"></span> Online
            </div>
        </div>
    </div>

    <!-- Pesan -->
    <div id="chatMessages" style="flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:10px">
        <div style="text-align:center;color:var(--text-muted);font-size:.78rem">Memuat...</div>
    </div>

    <!-- Input -->
    <div style="padding:14px 20px;border-top:1px solid var(--border)">
        <div style="display:flex;gap:10px;align-items:flex-end">
            <textarea id="msgInput" placeholder="Ketik pesan..." rows="2"
                style="flex:1;background:var(--bg-base);border:1px solid var(--border);border-radius:8px;padding:10px 14px;color:var(--text-primary);font-family:'Sora',sans-serif;font-size:.83rem;resize:none;outline:none"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMsg()}"
                onfocus="this.style.borderColor='var(--accent)'"
                onblur="this.style.borderColor='var(--border)'"></textarea>
            <button onclick="sendMsg()" style="padding:10px 18px;background:var(--accent);color:#fff;border:none;border-radius:8px;font-size:.83rem;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:6px">
                <i class="bi bi-send"></i> Kirim
            </button>
        </div>
    </div>
</div>

<script>
const AJAX = '<?= BASE_URL ?>user/chat/ajax.php';
let lastId = 0;

function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function fetchMessages() {
    fetch(`${AJAX}?action=fetch&after_id=${lastId}`)
        .then(r=>r.json())
        .then(msgs => {
            const box = document.getElementById('chatMessages');
            if (!msgs.length) {
                if (lastId===0) box.innerHTML = '<div style="text-align:center;color:var(--text-muted);font-size:.78rem;padding:20px">Belum ada pesan. Mulai percakapan!</div>';
                return;
            }
            if (lastId===0) box.innerHTML='';
            msgs.forEach(m => {
                const isUser = m.sender === 'user';
                const div = document.createElement('div');
                div.style.cssText = `display:flex;justify-content:${isUser?'flex-end':'flex-start'}`;
                div.innerHTML = `
                    <div style="max-width:72%;padding:9px 14px;border-radius:${isUser?'12px 12px 2px 12px':'12px 12px 12px 2px'};background:${isUser?'var(--accent)':'var(--bg-base)'};color:${isUser?'#fff':'var(--text-primary)'};font-size:.83rem;line-height:1.5;border:${isUser?'none':'1px solid var(--border)'}">
                        ${escHtml(m.message)}
                        <div style="font-size:.65rem;opacity:.6;margin-top:4px;text-align:right">${m.created_at.slice(11,16)}</div>
                    </div>`;
                box.appendChild(div);
                lastId = Math.max(lastId, parseInt(m.id));
            });
            box.scrollTop = box.scrollHeight;
        });
}

function sendMsg() {
    const inp = document.getElementById('msgInput');
    const msg = inp.value.trim();
    if (!msg) return;
    inp.value = '';
    const fd = new FormData();
    fd.append('action','send');
    fd.append('message', msg);
    fetch(AJAX, {method:'POST',body:fd})
        .then(()=>fetchMessages());
}

fetchMessages();
setInterval(fetchMessages, 3000);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>