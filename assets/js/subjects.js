const subjectModal = document.getElementById('subjectModal');
const subjectForm = document.getElementById('subjectForm');
const onlineCheckbox = document.getElementById('f_online');
const onlineFields = document.getElementById('onlineFields');

onlineCheckbox.addEventListener('change', () => {
    onlineFields.style.display = onlineCheckbox.checked ? 'grid' : 'none';
});

function openSubjectModal() {
    subjectForm.reset();
    document.getElementById('f_id').value = '';
    document.getElementById('subjectModalTitle').textContent = 'Thêm môn học';
    onlineFields.style.display = 'none';
    subjectModal.classList.add('open');
}

function closeSubjectModal() {
    subjectModal.classList.remove('open');
}

function editSubject(subject) {
    document.getElementById('f_id').value = subject.id;
    document.getElementById('f_code').value = subject.subject_code;
    document.getElementById('f_name').value = subject.subject_name;
    document.getElementById('f_credits').value = subject.credits;
    document.getElementById('f_semester').value = subject.semester;
    document.getElementById('f_lecturer').value = subject.lecturer || '';
    document.getElementById('f_status').value = subject.status;
    document.getElementById('f_color').value = subject.color || '#8bc53f';
    onlineCheckbox.checked = !!Number(subject.is_online);
    onlineFields.style.display = onlineCheckbox.checked ? 'grid' : 'none';
    document.getElementById('f_platform').value = subject.online_platform || '';
    document.getElementById('f_link').value = subject.online_link || '';
    document.getElementById('subjectModalTitle').textContent = 'Sửa môn học';
    subjectModal.classList.add('open');
}

async function deleteSubject(id) {
    if (!confirm('Xoá môn học này? Toàn bộ điểm, lịch học và điểm danh liên quan cũng sẽ bị xoá.')) return;
    await apiCall('api/subjects.php', 'DELETE', { id });
    document.querySelector(`tr[data-id="${id}"]`)?.remove();
}

subjectForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(subjectForm);
    const data = Object.fromEntries(fd.entries());
    data.is_online = onlineCheckbox.checked ? 1 : 0;
    try {
        if (data.id) {
            await apiCall('api/subjects.php', 'PUT', data);
        } else {
            await apiCall('api/subjects.php', 'POST', data);
        }
        location.reload();
    } catch (err) {
        alert(err.message);
    }
});
