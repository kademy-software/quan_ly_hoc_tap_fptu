<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();
$sid = $_SESSION['student_id'];

// Truy vấn lấy dữ liệu, đã sắp xếp theo term (semester)
$stmt = $db->prepare('SELECT * FROM subjects WHERE student_id = ? ORDER BY semester DESC, subject_name');
$stmt->execute([$sid]);
$subjects = $stmt->fetchAll();

$compStmt = $db->prepare('SELECT * FROM grade_components WHERE subject_id = ? ORDER BY id');

// Nhóm các môn học theo kỳ (term/semester)
$subjectsByTerm = [];
foreach ($subjects as $s) {
    $term = !empty($s['semester']) ? $s['semester'] : 'Chưa phân kỳ';
    $subjectsByTerm[$term][] = $s;
}

$pageTitle = 'Điểm số';
$activePage = 'grades';
$extraScripts = ['assets/js/grades.js'];
include __DIR__ . '/includes/header.php';
?>

<div class="card-header">
    <h1 style="margin:0;">Quản lý điểm</h1>
</div>

<?php if (!$subjects): ?>
    <div class="card" style="margin-top:16px;">
        <div class="empty-state">Chưa có môn học nào để quản lý điểm.</div>
    </div>
<?php else: ?>
    <?php foreach ($subjectsByTerm as $term => $termSubjects): ?>
        <!-- Tiêu đề Học kỳ -->
        <h3 style="margin-top: 24px; margin-bottom: 8px; color: #333; display: flex; align-items: center;">
            <span style="display: inline-block; width: 6px; height: 20px; background: #a4d037; margin-right: 8px; border-radius: 4px;"></span>
            Học kỳ: <?= htmlspecialchars($term) ?>
        </h3>
        
        <!-- Bảng điểm của học kỳ tương ứng -->
        <div class="card" style="margin-top: 8px; border: 1px solid #a4d037; padding: 0;">
            <table style="width: 100%; border-collapse: collapse; margin: 0;">
                <thead>
                    <tr>
                        <th style="border-bottom: 2px solid #a4d037; text-align: left; padding: 12px; width: 25%;">Môn học</th>
                        <th style="border-bottom: 2px solid #a4d037; text-align: left; padding: 12px; width: 12%;">Trạng thái</th>
                        <th style="border-bottom: 2px solid #a4d037; text-align: left; padding: 12px; width: 38%;">Chi tiết đầu điểm</th>
                        <th style="border-bottom: 2px solid #a4d037; text-align: center; padding: 12px; width: 10%;">Điểm tổng</th>
                        <th style="border-bottom: 2px solid #a4d037; text-align: center; padding: 12px; width: 15%;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($termSubjects as $s): 
                    // Áp dụng logic trạng thái từ subjects.php
                    $map = [
                        'ongoing' => ['Đang học','badge-blue'], 
                        'completed' => ['Passed','badge-green'], 
                        'not_started' => ['Chưa bắt đầu','badge-gray'],
                        'exempted' => ['Miễn môn','badge-purple']
                    ];
                    $status_key = $s['status'] ?? 'not_started';
                    [$label, $cls] = $map[$status_key] ?? ['Không xác định', 'badge-gray'];
                    
                    $isExempt = ($status_key === 'exempted');
                    $weightedScore = 0; $weightSum = 0; $hasScore = false; $components = [];
                    
                    if (!$isExempt) {
                        $compStmt->execute([$s['id']]);
                        $components = $compStmt->fetchAll();
                        
                        foreach ($components as $c) {
                            $weightSum += (float)$c['weight_percent'];
                            if ($c['score'] !== null) {
                                $hasScore = true;
                                $weightedScore += ((float)$c['score'] / (float)$c['max_score']) * 10 * ((float)$c['weight_percent'] / 100);
                            }
                        }
                    }
                ?>
                    <tr data-subject="<?= $s['id'] ?>" style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px; vertical-align: top;">
                            <span class="dot" style="background:<?= htmlspecialchars($s['color']) ?>"></span> 
                            <strong><?= htmlspecialchars($s['subject_code']) ?></strong><br>
                            <span style="font-size: 13px; color: #555;"><?= htmlspecialchars($s['subject_name']) ?></span>
                        </td>
                        <td style="padding: 12px; vertical-align: top;">
                            <span class="badge <?= $cls ?>"><?= $label ?></span>
                        </td>
                        
                        <td style="padding: 12px; vertical-align: top;">
                            <?php if ($isExempt): ?>
                                <div style="color: #8e44ad; font-size: 14px; font-weight: bold; margin-top: 4px;">
                                    Môn học này được miễn
                                </div>
                            <?php else: ?>
                                <?php if ($components): ?>
                                    <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                                        <?php foreach ($components as $c): ?>
                                            <tr data-comp-id="<?= $c['id'] ?>">
                                                <td style="padding: 4px 0; border: none; width: 45%;"><?= htmlspecialchars($c['component_name']) ?></td>
                                                <td style="padding: 4px 0; border: none; width: 15%; color: #666;"><?= number_format((float)$c['weight_percent'], 0) ?>%</td>
                                                <td style="padding: 4px 0; border: none; width: 30%;">
                                                    <input type="number" step="0.1" min="0" value="<?= $c['score'] !== null ? htmlspecialchars($c['score']) : '' ?>"
                                                           style="width:55px; padding: 2px 4px; border: 1px solid #a4d037; border-radius: 4px; outline: none;" onchange="updateScore(<?= $c['id'] ?>, this.value)">
                                                    / <?= number_format((float)$c['max_score'], 0) ?>
                                                </td>
                                                <td style="padding: 4px 0; border: none; text-align: right; width: 10%;">
                                                    <button class="icon-btn" style="padding:0; background:none; border:none; cursor:pointer;" onclick="deleteComponent(<?= $c['id'] ?>, <?= $s['id'] ?>)">🗑️</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </table>
                                    <?php if ($weightSum != 100): ?>
                                        <div style="color:var(--orange, #e67e22); font-size:12px; margin-top:6px;">⚠ Tổng trọng số: <?= $weightSum ?>%</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div style="color: #888; font-size: 13px; margin-top: 4px;">Chưa có đầu điểm.</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        
                        <td style="padding: 12px; text-align: center; vertical-align: middle;">
                            <?php if ($isExempt): ?>
                                <span class="badge badge-purple" style="font-size:15px; padding:6px 14px;">Miễn</span>
                            <?php else: ?>
                                <span class="badge" style="font-size:15px; padding:6px 14px; background: #a4d037; color: white;">
                                    <?= $hasScore ? number_format($weightedScore, 2) : '—' ?> / 10
                                </span>
                            <?php endif; ?>
                        </td>
                        
                        <td style="padding: 12px; text-align: center; vertical-align: middle;">
                            <?php if (!$isExempt): ?>
                                <button class="btn btn-outline btn-sm" style="border-color: #a4d037; color: #a4d037; white-space: nowrap;" onclick="openComponentModal(<?= $s['id'] ?>)">+ Đầu điểm</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal thêm đầu điểm -->
<div class="modal-backdrop" id="componentModal">
    <div class="modal">
        <div class="card-header">
            <h2>Thêm đầu điểm</h2>
            <span class="modal-close" style="cursor: pointer;" onclick="document.getElementById('componentModal').classList.remove('open')">&times;</span>
        </div>
        <form id="componentForm">
            <input type="hidden" name="subject_id" id="c_subject_id">
            <div class="form-row">
                <label>Tên đầu điểm</label>
                <input name="component_name" placeholder="Quiz, Assignment, PE, FE..." required style="border: 1px solid #ccc; padding: 8px; border-radius: 4px;">
            </div>
            <div class="form-grid">
                <div class="form-row">
                    <label>Trọng số (%)</label>
                    <input type="number" name="weight_percent" required style="border: 1px solid #ccc; padding: 8px; border-radius: 4px;">
                </div>
                <div class="form-row">
                    <label>Thang điểm tối đa</label>
                    <input type="number" name="max_score" value="10" required style="border: 1px solid #ccc; padding: 8px; border-radius: 4px;">
                </div>
            </div>
            <button type="submit" class="btn btn-accent" style="width:100%; background: #a4d037; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; margin-top: 10px;">Lưu</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>