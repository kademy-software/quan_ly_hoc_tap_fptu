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
['subjectModal', 'fapModal', 'attendanceModal', 'todoModal', 'moduleModal'].forEach(closeOnBackdropClick);

// Dropdown click handler (ho tro click/cham tren tablet va mobile)
(function () {
    const dropdowns = document.querySelectorAll('.nav-dropdown');
    dropdowns.forEach(dd => {
        const btn = dd.querySelector('.nav-drop-btn');
        if (!btn) return;
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = dd.classList.contains('open');
            // Dong cac dropdown khac
            dropdowns.forEach(other => { if (other !== dd) other.classList.remove('open'); });
            dd.classList.toggle('open', !isOpen);
        });
    });

    document.addEventListener('click', (e) => {
        dropdowns.forEach(dd => {
            if (!dd.contains(e.target)) dd.classList.remove('open');
        });
    });
})();
