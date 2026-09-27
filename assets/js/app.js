// Helpers dung chung toan he thong QLHT

async function apiCall(url, method = 'GET', body = null) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(url, opts);
    if (!res.ok) {
        const err = await res.json().catch(() => ({ error: 'Có lỗi xảy ra' }));
        throw new Error(err.error || 'Có lỗi xảy ra');
    }
    return res.json();
}

function closeOnBackdropClick(modalId) {
    const el = document.getElementById(modalId);
    if (!el) return;
    el.addEventListener('click', (e) => {
        if (e.target === el) el.classList.remove('open');
    });
}
['subjectModal', 'fapModal', 'attendanceModal'].forEach(closeOnBackdropClick);
