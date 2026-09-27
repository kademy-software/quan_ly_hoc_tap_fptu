const createModal = document.getElementById('createModal');
const manualForm = document.getElementById('manualForm');
const importForm = document.getElementById('importForm');

function openCreateModal() {
    createModal.classList.add('open');
    showTab('manual');
}

function showTab(tab) {
    if (tab === 'manual') {
        manualForm.style.display = 'block';
        importForm.style.display = 'none';
    } else {
        manualForm.style.display = 'none';
        importForm.style.display = 'block';
    }
}

manualForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(manualForm).entries());
    data.action = 'create';
    try {
        const res = await apiCall('api/assessments.php', 'POST', data);
        alert('Tạo bài thi thành công! Bây giờ hãy thêm câu hỏi.');
        window.location.href = `edit_assessment.php?id=${res.id}`;
    } catch (err) {
        alert(err.message);
    }
});

importForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const jsonStr = document.getElementById('jsonInput').value.trim();
    try {
        const res = await apiCall('api/assessments.php', 'POST', {
            action: 'import_json',
            json: jsonStr
        });
        alert('Import bài thi thành công!');
        location.reload();
    } catch (err) {
        alert('Lỗi: ' + err.message);
    }
});

async function deleteAssessment(id) {
    if (!confirm('Xoá bài thi này?')) return;
    try {
        await apiCall('api/assessments.php', 'DELETE', { id });
        location.reload();
    } catch (err) {
        alert(err.message);
    }
}
