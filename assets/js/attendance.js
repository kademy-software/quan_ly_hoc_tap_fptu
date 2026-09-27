const attendanceModal = document.getElementById('attendanceModal');
const attendanceForm = document.getElementById('attendanceForm');

function openAttendanceModal() {
    attendanceForm.reset();
    attendanceModal.classList.add('open');
}

attendanceForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(attendanceForm).entries());
    try {
        await apiCall('api/attendance.php', 'POST', data);
        location.reload();
    } catch (err) {
        alert(err.message);
    }
});

async function deleteAttendance(id) {
    if (!confirm('Xoá bản ghi điểm danh này?')) return;
    await apiCall('api/attendance.php', 'DELETE', { id });
    document.querySelector(`tr[data-id="${id}"]`)?.remove();
}
