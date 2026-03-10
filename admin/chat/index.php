<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

$page_title = 'Chat';
require_once __DIR__ . '/../../admin/includes/header.php';
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center">
    <div>
        <h1>Chat dengan User</h1>
        <p>Balas pesan langsung dari pelanggan</p>
    </div>
</div>

<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;overflow:hidden;display:flex;height:calc(100vh - 200px);min-height:500px">

    <!-- Sidebar daftar user -->
    <div style="width:280px;border-right:1px solid var(--border);display:flex;flex-direction:column;flex-shrink:0">
        <div style="padding:14px 16px;border-bottom:1px solid var(--border)">
            <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted)">Percakapan</div>
        </div>
        <div id="userList" style="flex:1;overflow-y:auto">
            <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:.8rem">Memuat...</div>
        </div>
    </div>

    <!-- Area chat -->
    <div style="flex:1;display:flex;flex-direction:column">
        <!-- Header chat -->
        <div id="chatHeader" style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;min-height:57px">
            <div style="color:var(--text-muted);font-size:.83rem">Pilih percakapan untuk memulai</div>
        </div>

        <!-- Pesan -->
        <div id="chatMessages" style="flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:10px">
        </div>

        <!-- Input -->
        <div id="chatInput" style="display:none;padding:14px 20px;border-top:1px solid var(--border)">
            <div style="display:flex;gap:10px;align-items:flex-end">
                <textarea id="msgInput" placeholder="Ketik balasan..." rows="2"
                    style="flex:1;background:var(--bg-base);border:1px solid var(--border);border-radius:8px;padding:10px 14px;color:var(--text-primary);font-family:'Sora',sans-serif;font-size:.83rem;resize:none;outline:none"
                    onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMsg()}"
                    onfocus="this.style.borderColor='var(--accent)'"
                    onblur="this.style.borderColor='var(--border)'"></textarea>
                <button onclick="sendMsg()" style="padding:10px 18px;background:var(--accent);color:#fff;border:none;border-radius:8px;font-size:.83rem;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap">
                    <i class="bi bi-send"></i> Kirim
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const AJAX = '<?= BASE_URL ?>admin/chat/ajax.php';
let activeUser = null;
let lastId = 0;
let pollTimer = null;

function timeAgo(dt) {
    const d = new Date(dt.replace(' ','T'));
    const diff = Math.floor((Date.now() - d)/1000);
    if (diff < 60) return diff + 'd';
    if (diff < 3600) return Math.floor(diff/60) + 'm';
    if (diff < 86400) return Math.floor(diff/3600) + 'j';
    return d.toLocaleDateString('id-ID',{day:'2-digit',month:'short'});
}

function initials(name) {
    return name.split(' ').slice(0,2).map(w=>w[0]).join('').toUpperCase();
}

// Load daftar user
function loadUserList() {
    fetch(AJAX + '?action=user_list')
        .then(r=>r.json())
        .then(list => {
            const el = document.getElementById('userList');
            if (!list.length) {
                el.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:.8rem">Belum ada pesan masuk</div>';
                return;
            }
            el.innerHTML = list.map(u => `
                <div onclick="openChat(${u.id},'${escHtml(u.name)}')"
                     id="userItem-${u.id}"
                     style="padding:12px 16px;cursor:pointer;border-bottom:1px solid var(--border);display:flex;gap:10px;align-items:center;transition:background .15s"
                     onmouseover="this.style.background='var(--bg-card-hover)'"
                     onmouseout="this.style.background=activeUser?.id===${u.id}?'var(--accent-soft)':'transparent'">
                    <div style="width:36px;height:36px;border-radius:50%;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:var(--accent);flex-shrink:0">
                        ${escHtml(initials(u.name))}
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <span style="font-size:.82rem;font-weight:500;color:var(--text-primary)">${escHtml(u.name)}</span>
                            <span style="font-size:.68rem;color:var(--text-muted)">${timeAgo(u.last_msg)}</span>
                        </div>
                        <div style="font-size:.73rem;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px">${escHtml(u.last_text||'')}</div>
                    </div>
                    ${parseInt(u.unread)>0 ? `<span style="background:var(--accent);color:#fff;font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:999px;flex-shrink:0">${u.unread}</span>` : ''}
                </div>
            `).join('');

            // Highlight active user
            if (activeUser) highlightUser(activeUser.id);
        });
}

function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function highlightUser(id) {
    document.querySelectorAll('[id^="userItem-"]').forEach(el => {
        el.style.background = el.id === 'userItem-'+id ? 'var(--accent-soft)' : 'transparent';
    });
}

function openChat(userId, userName) {
    activeUser = {id: userId, name: userName};
    lastId = 0;
    clearInterval(pollTimer);
    highlightUser(userId);

    document.getElementById('chatHeader').innerHTML = `
        <div style="width:36px;height:36px;border-radius:50%;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:var(--accent);flex-shrink:0">
            ${escHtml(initials(userName))}
        </div>
        <div>
            <div style="font-weight:600;font-size:.88rem;color:var(--text-primary)">${escHtml(userName)}</div>
            <div style="font-size:.7rem;color:var(--text-muted)">User</div>
        </div>
    `;
    document.getElementById('chatInput').style.display = 'block';
    document.getElementById('chatMessages').innerHTML = '<div style="text-align:center;color:var(--text-muted);font-size:.78rem;padding:20px">Memuat pesan...</div>';

    fetchMessages();
    pollTimer = setInterval(() => { fetchMessages(); loadUserList(); }, 3000);
}

function fetchMessages() {
    if (!activeUser) return;
    fetch(`${AJAX}?action=fetch&user_id=${activeUser.id}&after_id=${lastId}`)
        .then(r=>r.json())
        .then(msgs => {
            if (!msgs.length) {
                if (lastId === 0) {
                    document.getElementById('chatMessages').innerHTML = '<div style="text-align:center;color:var(--text-muted);font-size:.78rem;padding:20px">Belum ada pesan</div>';
                }
                return;
            }
            const box = document.getElementById('chatMessages');
            if (lastId === 0) box.innerHTML = '';
            msgs.forEach(m => {
                const isAdmin = m.sender === 'admin';
                const div = document.createElement('div');
                div.style.cssText = `display:flex;justify-content:${isAdmin?'flex-end':'flex-start'}`;
                div.innerHTML = `
                    <div style="max-width:72%;padding:9px 14px;border-radius:${isAdmin?'12px 12px 2px 12px':'12px 12px 12px 2px'};background:${isAdmin?'var(--accent)':'var(--bg-base)'};color:${isAdmin?'#fff':'var(--text-primary)'};font-size:.83rem;line-height:1.5">
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
    if (!activeUser) return;
    const inp = document.getElementById('msgInput');
    const msg = inp.value.trim();
    if (!msg) return;
    inp.value = '';
    const fd = new FormData();
    fd.append('action','send');
    fd.append('user_id', activeUser.id);
    fd.append('message', msg);
    fetch(AJAX, {method:'POST',body:fd})
        .then(()=>{ fetchMessages(); loadUserList(); });
}

// Init
loadUserList();
setInterval(loadUserList, 5000);
</script>

<?php require_once __DIR__ . '/../../admin/includes/footer.php'; ?>