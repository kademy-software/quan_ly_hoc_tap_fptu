const componentModal = document.getElementById('componentModal');
const componentForm = document.getElementById('componentForm');

function openComponentModal(subjectId) {
    componentForm.reset();
    document.getElementById('c_subject_id').value = subjectId;
    componentModal.classList.add('open');
}

componentForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(componentForm).entries());
    try {
        await apiCall('api/grades.php', 'POST', data);
        location.reload();
    } catch (err) {
        alert(err.message);
    }
});

let scoreTimers = {};
function updateScore(id, value) {
    clearTimeout(scoreTimers[id]);
    scoreTimers[id] = setTimeout(async () => {
        try {
            await apiCall('api/grades.php', 'PUT', { id, score: value });
        } catch (err) {
            alert(err.message);
        }
    }, 500);
}

async function deleteComponent(id, subjectId) {
    if (!confirm('Xoá đầu điểm này?')) return;
    await apiCall('api/grades.php', 'DELETE', { id });
    document.querySelector(`tr[data-comp-id="${id}"]`)?.remove();
}
