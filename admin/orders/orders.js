const EXPEDITION_URLS = {
    jne:      "https://www.jne.co.id/id/tracking/trace/",
    jnt:      "https://www.jet.co.id/track/",
    sicepat:  "https://www.sicepat.com/checkAwb/",
    pos:      "https://www.posindonesia.co.id/id/tracking/",
    tiki:     "https://tiki.id/id/tracking?awb/",
    anteraja: "https://anteraja.id/tracking/",
    ninja:    "https://www.ninjaxpress.co/id-id/tracking?id=",
};

const EXPEDITION_NAMES = {
    jne: "JNE", jnt: "J&T Express", sicepat: "SiCepat",
    pos: "Pos Indonesia", tiki: "TIKI", anteraja: "AnterAja", ninja: "Ninja Express"
};

function formatRp(n) {
    return "Rp " + parseInt(n).toLocaleString("id-ID");
}

function openDetail(id) {
    const o = ORDERS[id];
    if (!o) return;

    document.getElementById("detailTitle").textContent = "Detail Pesanan #" + o.id;
    const sl = STATUS_LABEL[o.status] || {label: o.status, color: "#fff"};

    let itemsHtml = "";
    o.items.forEach(item => {
        const coverHtml = item.cover
            ? `<img src="${BASE_URL}assets/uploads/covers/${item.cover}" style="width:100%;height:100%;object-fit:cover">`
            : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center"><i class="bi bi-book" style="color:var(--text-muted);font-size:.7rem"></i></div>`;
        itemsHtml += `
        <div style="display:flex;gap:12px;align-items:center;padding:10px 0;border-bottom:1px solid var(--border)">
            <div style="width:40px;height:52px;border-radius:5px;overflow:hidden;flex-shrink:0;background:var(--bg-base);border:1px solid var(--border)">${coverHtml}</div>
            <div style="flex:1;min-width:0">
                <div style="font-size:.83rem;font-weight:500;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${item.title}</div>
                <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px">${item.author}</div>
            </div>
            <div style="text-align:right;flex-shrink:0">
                <div style="font-size:.75rem;color:var(--text-muted)">x${item.quantity}</div>
                <div style="font-size:.82rem;font-weight:600;color:var(--accent)">${formatRp(item.price * item.quantity)}</div>
            </div>
        </div>`;
    });

    let trackingHtml = "";
    if (o.tracking_number) {
        const tUrl  = (EXPEDITION_URLS[o.expedition] || "#") + o.tracking_number;
        const tName = (EXPEDITION_NAMES[o.expedition] || o.expedition || "-").toUpperCase();
        trackingHtml = `
        <div style="margin-top:14px;background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.15);border-radius:8px;padding:12px 14px">
            <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--accent);margin-bottom:8px;display:flex;align-items:center;gap:5px">
                <i class="bi bi-truck"></i> Info Pengiriman
            </div>
            <div style="display:flex;justify-content:space-between;font-size:.8rem;margin-bottom:6px">
                <span style="color:var(--text-muted)">Ekspedisi</span>
                <span style="color:var(--text-primary);font-weight:500">${tName}</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:.8rem;margin-bottom:8px">
                <span style="color:var(--text-muted)">No. Resi</span>
                <span style="color:var(--text-primary);font-weight:600;font-family:monospace">${o.tracking_number}</span>
            </div>
            <a href="${tUrl}" target="_blank"
               style="display:flex;align-items:center;justify-content:center;gap:6px;padding:7px;background:var(--accent);color:#fff;border-radius:7px;text-decoration:none;font-size:.78rem;font-weight:500">
                <i class="bi bi-box-arrow-up-right"></i> Lacak Paket
            </a>
        </div>`;
    }

    document.getElementById("detailBody").innerHTML = `
        <div style="background:var(--bg-base);border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:16px">
            <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:8px">Informasi Pembeli</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:.8rem">
                <div><div style="color:var(--text-muted);font-size:.7rem">Nama</div><div style="color:var(--text-primary);font-weight:500;margin-top:2px">${o.user_name}</div></div>
                <div><div style="color:var(--text-muted);font-size:.7rem">Email</div><div style="color:var(--text-primary);margin-top:2px">${o.user_email}</div></div>
                <div><div style="color:var(--text-muted);font-size:.7rem">No. HP</div><div style="color:var(--text-primary);margin-top:2px">${o.user_phone}</div></div>
                <div><div style="color:var(--text-muted);font-size:.7rem">Tanggal</div><div style="color:var(--text-primary);margin-top:2px">${o.created_at}</div></div>
            </div>
            <div style="margin-top:8px"><div style="color:var(--text-muted);font-size:.7rem">Alamat Pengiriman</div>
            <div style="color:var(--text-primary);font-size:.8rem;margin-top:2px;line-height:1.5">${o.shipping_address}</div></div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <span style="font-size:.78rem;color:var(--text-muted)">Status Pesanan</span>
            <span style="background:${sl.color}22;color:${sl.color};padding:4px 12px;border-radius:999px;font-size:.72rem;font-weight:600">${sl.label}</span>
        </div>
        <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:4px">Buku Dipesan</div>
        <div>${itemsHtml}</div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding-top:12px;margin-top:4px">
            <span style="font-size:.83rem;color:var(--text-muted)">Total Pembayaran</span>
            <span style="font-size:1.05rem;font-weight:700;color:var(--accent)">${formatRp(o.total_price)}</span>
        </div>
        ${trackingHtml}
    `;

    document.getElementById("detailModal").style.display = "flex";
}

function closeDetail() {
    document.getElementById("detailModal").style.display = "none";
}

// FUNGSI INI YANG KITA PERBAIKI TOTAL
function openModal(id, currentStatus) {
    console.log("Membuka Modal untuk ID:", id, "Status:", currentStatus);
    
    const idInput = document.getElementById("modalOrderId");
    const selectStatus = document.getElementById("modalStatus");
    
    if (!idInput || !selectStatus) {
        console.error("Elemen modal tidak ditemukan di HTML!");
        return;
    }

    idInput.value = id;
    selectStatus.innerHTML = ''; 

    // Daftar status aman
    const flow = {
        'pending': ['pending', 'processing', 'cancelled'],
        'processing': ['processing', 'shipped', 'cancelled'],
        'shipped': ['shipped', 'delivered'],
        'delivered': ['delivered'],
        'cancelled': ['cancelled']
    };

    // Ambil opsi, jika status aneh, default ke status itu sendiri
    let options = flow[currentStatus];
    if (!options) {
        options = [currentStatus];
    }

    // Isi Dropdown
    options.forEach(statusKey => {
        const opt = document.createElement('option');
        opt.value = statusKey;
        opt.textContent = statusKey.charAt(0).toUpperCase() + statusKey.slice(1);
        if (statusKey === currentStatus) opt.selected = true;
        selectStatus.appendChild(opt);
    });

    // Reset dan isi data ekspedisi
    const orderData = ORDERS[id] || {};
    const expInput = document.getElementById("modalExpedition");
    const trackInput = document.getElementById("modalTracking");

    if (expInput) expInput.value = orderData.expedition || "";
    if (trackInput) trackInput.value = orderData.tracking_number || "";
    
    toggleTrackingFields();
    updateTrackingLink();
    
    document.getElementById("statusModal").style.display = "flex";
}

function closeModal() {
    document.getElementById("statusModal").style.display = "none";
}

function toggleTrackingFields() {
    const selectStatus = document.getElementById("modalStatus");
    const trackDiv = document.getElementById("trackingFields");
    if (selectStatus && trackDiv) {
        trackDiv.style.display = selectStatus.value === "shipped" ? "block" : "none";
    }
}

function updateTrackingLink() {
    const exp = document.getElementById("modalExpedition")?.value;
    const resi = document.getElementById("modalTracking")?.value.trim();
    const prev = document.getElementById("trackingPreview");
    const link = document.getElementById("trackingLink");
    const txt = document.getElementById("trackingLinkText");

    if (exp && resi && EXPEDITION_URLS[exp]) {
        if (link) link.href = EXPEDITION_URLS[exp] + resi.toUpperCase();
        if (txt) txt.textContent = "Cek di " + EXPEDITION_NAMES[exp] + " -> " + resi.toUpperCase();
        if (prev) prev.style.display = "block";
    } else {
        if (prev) prev.style.display = "none";
    }
}