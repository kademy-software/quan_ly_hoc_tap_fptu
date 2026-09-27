<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();
$sid = $_SESSION['student_id'];

$subjStmt = $db->prepare("SELECT * FROM subjects WHERE student_id = ? AND status != 'completed' ORDER BY subject_name");
$subjStmt->execute([$sid]);
$subjects = $subjStmt->fetchAll();

$slotStmt = $db->prepare('SELECT ss.*, s.subject_code, s.subject_name, s.color FROM schedule_slots ss JOIN subjects s ON s.id = ss.subject_id WHERE ss.student_id = ?');
$slotStmt->execute([$sid]);
$placed = [];
foreach ($slotStmt->fetchAll() as $row) {
    $placed[$row['day_of_week']][$row['slot_number']] = $row;
}

$days = [2 => 'Thứ 2', 3 => 'Thứ 3', 4 => 'Thứ 4', 5 => 'Thứ 5', 6 => 'Thứ 6', 7 => 'Thứ 7'];

$pageTitle = 'Lịch học';
$activePage = 'schedule';
$extraScripts = ['assets/js/schedule.js'];
include __DIR__ . '/includes/header.php';
?>

<h1>Lịch học (Slot 1 - 10)</h1>
<p style="color:var(--text-dim); margin-top:-8px;">Kéo môn học từ danh sách bên dưới thả vào ô slot tương ứng. Có thể kéo để di chuyển giữa các ô, nhấp đúp để đặt phòng học, hoặc nhấn ✕ để xoá.</p>

<div class="card" style="margin-bottom:16px;">
    <h3 style="margin-top:0;">Danh sách môn học (kéo để xếp lịch)</h3>
    <div class="subject-bank" id="subjectBank">
        <?php foreach ($subjects as $s): ?>
            <div class="subject-chip" draggable="true" data-subject-id="<?= $s['id'] ?>"
                 style="background:<?= htmlspecialchars($s['color']) ?>">
                <?= htmlspecialchars($s['subject_code']) ?>
                <small><?= htmlspecialchars($s['subject_name']) ?></small>
            </div>
        <?php endforeach; ?>
        <?php if (!$subjects): ?><div class="empty-state">Chưa có môn học nào. <a href="subjects.php">Thêm môn học</a> trước.</div><?php endif; ?>
    </div>
</div>

<div class="card schedule-scroll">
    <div class="schedule-grid" id="scheduleGrid">
        <div></div>
        <?php foreach ($days as $label): ?><div class="head-cell"><?= $label ?></div><?php endforeach; ?>

        <?php for ($slot = 1; $slot <= 10; $slot++): ?>
            <div class="slot-label">Slot<br><?= $slot ?></div>
            <?php foreach ($days as $dow => $label):
                $entry = $placed[$dow][$slot] ?? null; ?>
                <div class="slot-cell" data-day="<?= $dow ?>" data-slot="<?= $slot ?>">
                    <?php if ($entry): ?>
                        <div class="subject-chip" draggable="true"
                             data-schedule-id="<?= $entry['id'] ?>"
                             data-subject-id="<?= $entry['subject_id'] ?>"
                             data-room="<?= htmlspecialchars($entry['room'] ?? '') ?>"
                             style="background:<?= htmlspecialchars($entry['color']) ?>; position:relative;"
                             title="Nhấp đúp để đặt phòng · Kéo để di chuyển">
                            <span onclick="removeSlot(event, <?= $entry['id'] ?>)" style="position:absolute; top:2px; right:5px; cursor:pointer; font-weight:800;">✕</span>
                            <?= htmlspecialchars($entry['subject_code']) ?>
                            <small><?= htmlspecialchars($entry['room'] ?: 'Chưa có phòng') ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endfor; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
