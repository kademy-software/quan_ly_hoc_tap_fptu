const fapModal = document.getElementById('fapModal');
const fapForm = document.getElementById('fapForm');

function openFapModal() {
    fapForm.reset();
    fapModal.classList.add('open');
}

fapForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(fapForm).entries());
    try {
        await apiCall('api/fap.php', 'POST', data);
        location.reload();
    } catch (err) {
        alert(err.message);
    }
});

async function updateFapStatus(id, status) {
    try {
        await apiCall('api/fap.php', 'PUT', { id, status });
    } catch (err) {
        alert(err.message);
    }
}

async function deleteFap(id) {
    if (!confirm('Xoá đơn này?')) return;
    await apiCall('api/fap.php', 'DELETE', { id });
    document.querySelector(`tr[data-id="${id}"]`)?.remove();
}
