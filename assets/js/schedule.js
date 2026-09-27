// Keo tha mon hoc vao luoi slot 1-10

let dragData = null; // { subjectId, scheduleId (neu di chuyen o da xep) }

function attachChipEvents(chip) {
    chip.addEventListener('dragstart', (e) => {
        dragData = {
            subjectId: chip.dataset.subjectId,
            scheduleId: chip.dataset.scheduleId || null,
        };
        chip.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });
    chip.addEventListener('dragend', () => chip.classList.remove('dragging'));

    if (chip.dataset.scheduleId) {
        chip.addEventListener('dblclick', async () => {
            const room = prompt('Nhập tên phòng học:', chip.dataset.room || '');
            if (room === null) return;
            try {
                await apiCall('api/schedule.php', 'PUT', { schedule_id: chip.dataset.scheduleId, room });
                location.reload();
            } catch (err) {
                alert(err.message);
            }
        });
    }
}

document.querySelectorAll('.subject-chip').forEach(attachChipEvents);

document.querySelectorAll('.slot-cell').forEach((cell) => {
    cell.addEventListener('dragover', (e) => {
        e.preventDefault();
        cell.classList.add('drag-over');
    });
    cell.addEventListener('dragleave', () => cell.classList.remove('drag-over'));

    cell.addEventListener('drop', async (e) => {
        e.preventDefault();
        cell.classList.remove('drag-over');
        if (!dragData) return;
        const day = cell.dataset.day;
        const slot = cell.dataset.slot;

        try {
            if (dragData.scheduleId) {
                await apiCall('api/schedule.php', 'PUT', {
                    schedule_id: dragData.scheduleId,
                    day_of_week: day,
                    slot_number: slot,
                });
            } else {
                await apiCall('api/schedule.php', 'POST', {
                    subject_id: dragData.subjectId,
                    day_of_week: day,
                    slot_number: slot,
                });
            }
            location.reload();
        } catch (err) {
            alert(err.message);
        }
        dragData = null;
    });
});

async function removeSlot(event, scheduleId) {
    event.stopPropagation();
    if (!confirm('Bỏ môn học khỏi ô lịch này?')) return;
    try {
        await apiCall('api/schedule.php', 'DELETE', { schedule_id: scheduleId });
        location.reload();
    } catch (err) {
        alert(err.message);
    }
}
