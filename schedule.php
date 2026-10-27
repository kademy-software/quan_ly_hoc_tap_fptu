<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/fap_sync.php';
requireLogin();
$db  = getDB();
$sid = (int)$_SESSION['student_id'];

$fap       = fapGetRow($db,$sid);
$syncing   = isset($_GET['fap_sync']);
$importing = isset($_GET['fap_import']);
$apiBase   = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$today     = date('Y-m-d');

// Giờ bắt đầu - kết thúc từng slot (sửa tại đây nếu đổi giờ).
// Slot 1, 2, 5, 6 lấy từ dữ liệu FAP của bạn; 3, 4, 7, 8 suy ra theo nhịp 90' + nghỉ 15'.
// Slot khác (vd. slot 10 của THM) không có giờ thì chỉ hiện số slot.
$slotTimes = [
    1 => ['07:00', '08:30'], 2 => ['08:45', '10:15'], 3 => ['10:30', '12:00'], 4 => ['12:30', '14:00'],
    5 => ['14:15', '15:45'], 6 => ['16:00', '17:30'], 7 => ['17:45', '19:15'], 8 => ['19:30', '21:00'],
];

$stmt = $db->prepare('SELECT id, subject_code, subject_name, color, status FROM subjects WHERE student_id=? ORDER BY subject_code');
$stmt->execute([$sid]);
$subjects = $stmt->fetchAll();

$pageTitle  = 'Lịch học';
$activePage = 'schedule';
include __DIR__ . '/includes/header.php';
?>

<style>
.wk-bar{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.wk-bar .grp{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.wk-legend{display:flex;gap:14px;flex-wrap:wrap;font-size:12px;color:var(--text-dim);margin:10px 0 4px}
.wk-wrap{overflow-x:auto;margin-top:8px}
.wk{width:100%;min-width:940px;border-collapse:collapse;table-layout:fixed}
.wk th,.wk td{border:1px solid rgba(255,255,255,.08);padding:4px;vertical-align:top}
.wk th{font-weight:600;text-align:center;color:var(--text-dim)}
.wk th:first-child{width:86px}
.wk th.today{background:rgba(34,197,94,.18);color:inherit}
.wk td.today{background:rgba(34,197,94,.06)}
.wk .wk-date{font-size:12px;font-weight:400}
.wk .wk-slotn{font-weight:700;color:inherit}
.wk .wk-time{font-size:11px;font-weight:400}
.wk td.wk-cell{min-height:56px;height:56px;cursor:pointer}
.wk td.wk-cell:hover::after{content:'+';display:block;text-align:center;color:var(--text-dim);font-size:15px}
.wk td.dragover{outline:2px dashed #8bc53f;outline-offset:-3px}
.blk{border-left:4px solid #8bc53f;background:rgba(255,255,255,.07);border-radius:6px;padding:4px 7px;margin-bottom:4px;font-size:12px;line-height:1.35;cursor:pointer}
.blk-fixed{cursor:grab}
.blk-fap{background:rgba(56,189,248,.12);cursor:default}
.blk-event{background:rgba(255,255,255,.04)}
.blk-code{font-weight:700;word-break:break-word}
.blk-name,.blk-meta{color:var(--text-dim);font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tag{display:inline-block;font-size:10px;font-weight:400;padding:0 6px;border-radius:8px;background:rgba(255,255,255,.14);margin-left:4px}
.bank{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}
.bank-chip{cursor:grab;display:inline-block;user-select:none}
#slotModal option{background:#1e293b;color:#fff}
</style>

<h1>Lịch học</h1>
<p style="color:var(--text-dim); margin-top:-8px;">Xếp lịch cố định hằng tuần từ danh sách môn học của bạn (không cần FAP). Buổi học đồng bộ từ FAP sẽ được ưu tiên hiển thị nếu trùng ô; lịch cá nhân hiển thị ở hàng đầu mỗi ngày.</p>

<div class="card" style="margin-bottom:16px;">
    <div class="wk-bar">
        <div class="grp">
            <button type="button" class="btn" id="wkPrev"><i class="fa-solid fa-chevron-left"></i> Tuần trước</button>
            <button type="button" class="btn btn-accent" id="wkToday">Tuần này</button>
            <button type="button" class="btn" id="wkNext">Tuần sau <i class="fa-solid fa-chevron-right"></i></button>
            <input type="date" id="wkPick" title="Chọn 1 ngày để nhảy tới tuần đó" style="border-radius:6px; border:1px solid rgba(255,255,255,.2); background:rgba(0,0,0,.2); color:inherit; padding:5px 8px;">
        </div>
        <strong id="wkTitle" style="font-size:16px;"></strong>
        <div class="grp">
            <button type="button" class="btn btn-accent" id="addSlotBtn"><i class="fa-solid fa-thumbtack"></i> Xếp lịch cố định</button>
            <button type="button" class="btn" id="addEventBtn"><i class="fa-solid fa-plus"></i> Lịch cá nhân</button>
        </div>
    </div>

    <details open style="margin-top:12px;">
        <summary style="cursor:pointer; font-size:13px; color:var(--text-dim);">Kho môn học — kéo thả vào ô trên lịch (hoặc bấm vào môn để chọn thứ/slot)</summary>
        <div style="margin-top:8px; font-size:13px;">Hiện:
            <select id="bankFilter" style="border-radius:6px; border:1px solid rgba(255,255,255,.2); background:rgba(0,0,0,.25); color:inherit; padding:3px 6px;">
                <option value="ongoing">Đang học</option>
                <option value="not_started">Chưa học</option>
                <option value="done">Đã học / miễn giảm</option>
                <option value="all">Tất cả</option>
            </select>
        </div>
        <div class="bank" id="bank"></div>
    </details>

    <div class="wk-legend">
        <span><span class="tag" style="margin:0;">Cố định</span> tiết bạn tự xếp (kéo để di chuyển, bấm để sửa)</span>
        <span><span class="tag" style="margin:0; background:rgba(56,189,248,.3);">FAP</span> buổi đồng bộ từ FAP</span>
        <span>Bấm vào ô trống để thêm</span>
        <span id="wkMsg"></span>
    </div>
    <div class="wk-wrap"><table class="wk" id="wkTable"></table></div>
</div>

<div class="card" style="margin-bottom:16px;">
    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
        <button type="button" class="btn btn-accent" id="fapCookieModalBtn"><i class="fa-solid fa-key"></i> Cấu hình Cookie FAP</button>
        <button type="button" class="btn" id="fapSyncBtn" <?= ($syncing || $importing) ? 'disabled' : '' ?>><i class="fa-solid fa-rotate"></i> Cập nhật qua Automa</button>
        <button type="button" class="btn" id="fapBookmarkletBtn"><i class="fa-solid fa-bolt"></i> Đồng bộ 1 chạm (Bookmarklet)</button>
        <span id="fapSyncStatus" style="color:var(--text-dim);">
            <?php if ($importing): ?>Đang lưu lịch học từ FAP…
            <?php elseif ($syncing): ?>Đang chờ Automa lấy dữ liệu từ FAP…
            <?php elseif ($fap['last_sync_at']): ?>Cập nhật lần cuối: <?= htmlspecialchars($fap['last_sync_at']) ?> — <?= htmlspecialchars($fap['last_message'] ?? '') ?>
            <?php else: ?>Chưa cập nhật lần nào.<?php endif; ?>
        </span>
    </div>

    <details style="margin-top:14px;">
        <summary style="cursor:pointer; color:var(--text-dim); font-size:13px;">Thông tin kết nối Automa (cho ai dùng Extension Automa)</summary>
        <pre style="white-space:pre-wrap; user-select:all; margin-top:8px;">{"qlht_base": "<?= htmlspecialchars($apiBase) ?>", "token": "<?= htmlspecialchars($fap['token']) ?>"}</pre>
    </details>
</div>

<!-- Modal cấu hình FAP (Cookie + Khoảng thời gian lấy lịch) -->
<div class="modal-backdrop" id="cookieModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:999; align-items:center; justify-content:center;">
    <div class="modal" style="background:var(--card-bg, #1e293b); color:var(--text, #fff); border-radius:12px; padding:20px; width:92%; max-width:660px; max-height:92vh; overflow-y:auto; border:1px solid rgba(255,255,255,.1);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <h3 style="margin:0;"><i class="fa-solid fa-gear"></i> Cấu hình đồng bộ FAP</h3>
            <span style="cursor:pointer; font-size:20px; line-height:1;" onclick="document.getElementById('cookieModal').style.display='none'"><i class="fa-solid fa-xmark"></i></span>
        </div>

        <!-- Phần 1: Chọn khoảng ngày lấy lịch -->
        <div style="background:rgba(255,255,255,.05); border-radius:10px; padding:14px; margin-bottom:14px;">
            <h4 style="margin:0 0 8px 0; font-size:14px;"><i class="fa-solid fa-calendar-days"></i> Phạm vi lấy lịch học</h4>
            <p style="font-size:12.5px; color:var(--text-dim); margin:0 0 10px 0; line-height:1.5;">
                Chỉ những buổi học thuộc khoảng ngày này mới được lưu vào CSDL.<br>
                Để trống = lấy tất cả các buổi tìm thấy.
            </p>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <div>
                    <label style="font-size:12px; display:block; margin-bottom:2px; color:var(--text-dim);">Từ ngày:</label>
                    <input type="date" id="syncFromDateInput"
                           value="<?= htmlspecialchars($fap['sync_from_date'] ?? '') ?>"
                           style="border-radius:8px; border:1px solid rgba(255,255,255,.2); background:rgba(0,0,0,.25); color:inherit; padding:7px 10px; font-size:13px;">
                </div>
                <div>
                    <label style="font-size:12px; display:block; margin-bottom:2px; color:var(--text-dim);">Đến ngày:</label>
                    <input type="date" id="syncToDateInput"
                           value="<?= htmlspecialchars($fap['sync_to_date'] ?? '') ?>"
                           style="border-radius:8px; border:1px solid rgba(255,255,255,.2); background:rgba(0,0,0,.25); color:inherit; padding:7px 10px; font-size:13px;">
                </div>
            </div>
            <div style="margin-top:10px; display:flex; gap:8px;">
                <button type="button" class="btn" id="setTodayBtn" style="font-size:12px; padding:6px 12px;">Từ hôm nay</button>
                <button type="button" class="btn" id="clearDateBtn" style="font-size:12px; padding:6px 12px;">Xóa khoảng ngày</button>
            </div>
            <div id="syncDateRangeDisplay" style="margin-top:8px; font-size:12.5px; color:var(--text-dim);">
                <?php if (!empty($fap['sync_from_date']) || !empty($fap['sync_to_date'])): ?>
                    <i class="fa-solid fa-circle-check" style="color:#22c55e;"></i> Đang cấu hình lấy từ <strong><?= $fap['sync_from_date'] ? date('d/m/Y', strtotime($fap['sync_from_date'])) : 'đầu' ?></strong> đến <strong><?= $fap['sync_to_date'] ? date('d/m/Y', strtotime($fap['sync_to_date'])) : 'vô tận' ?></strong>.
                <?php else: ?>
                    <i class="fa-solid fa-circle-info" style="color:#38bdf8;"></i> Chưa cấu hình giới hạn ngày — sẽ lấy toàn bộ lịch.
                <?php endif; ?>
            </div>
        </div>

        <!-- Phần 2: Cookie FAP -->
        <div style="background:rgba(255,255,255,.05); border-radius:10px; padding:14px; margin-bottom:14px;">
            <h4 style="margin:0 0 8px 0; font-size:14px;"><i class="fa-solid fa-cookie-bite"></i> Cookie FAP</h4>
            <p style="font-size:12.5px; color:var(--text-dim); margin:0 0 8px 0; line-height:1.5;">
                Dán cookie từ trình duyệt: <code>__Host-FAP.Frontend.Auth</code>, <code>__Host-FAP.Frontend.Session</code>, <code>cf_clearance</code>.
            </p>
            <textarea id="cookieInput" rows="4"
                      style="width:100%; border-radius:8px; border:1px solid rgba(255,255,255,.15); background:rgba(0,0,0,.3); color:inherit; padding:8px; font-family:monospace; font-size:11.5px;"
                      placeholder="__Host-FAP.Frontend.Auth=...; __Host-FAP.Frontend.Session=...; cf_clearance=..."><?= htmlspecialchars($fap['fap_cookie'] ?? '') ?></textarea>
        </div>

        <!-- Nút lưu & cào -->
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:14px;">
            <button type="button" class="btn btn-accent" id="saveConfigBtn"><i class="fa-solid fa-floppy-disk"></i> Lưu cấu hình</button>
            <button type="button" class="btn" id="crawlServerBtn"><i class="fa-solid fa-rotate"></i> Cào từ Server ngay</button>
            <span id="cookieModalMsg" style="font-size:13px; color:var(--text-dim);"></span>
        </div>

        <hr style="border:0; border-top:1px solid rgba(255,255,255,.1); margin:6px 0 14px;">

        <!-- Phần 3: Nhập HTML trực tiếp -->
        <div>
            <h4 style="margin:0 0 6px 0; font-size:14px;"><i class="fa-solid fa-clipboard"></i> Nhập HTML từ trang FAP</h4>
            <p style="font-size:12.5px; color:var(--text-dim); margin-bottom:8px; line-height:1.5;">
                Mở FAP → nhấn <kbd>Ctrl+U</kbd> → copy toàn bộ → dán vào đây:
            </p>
            <textarea id="fapHtmlInput" rows="3"
                      style="width:100%; border-radius:8px; border:1px solid rgba(255,255,255,.15); background:rgba(0,0,0,.3); color:inherit; padding:8px; font-family:monospace; font-size:11.5px;"
                      placeholder="Dán mã nguồn HTML trang WeeklyTimetable vào đây..."></textarea>
            <button type="button" class="btn" style="margin-top:8px;" id="importHtmlBtn"><i class="fa-solid fa-file-import"></i> Bóc tách & Lưu lịch từ HTML</button>
        </div>
    </div>
</div>

<!-- Modal Bookmarklet Hướng dẫn -->
<div class="modal-backdrop" id="bookmarkletModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:999; align-items:center; justify-content:center;">
    <div class="modal" style="background:var(--card-bg, #1e293b); color:var(--text, #fff); border-radius:12px; padding:20px; width:92%; max-width:650px; max-height:90vh; overflow-y:auto; border:1px solid rgba(255,255,255,.1);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <h3 style="margin:0;"><i class="fa-solid fa-bolt"></i> Đồng bộ 1 chạm từ FAP (Khuyên dùng)</h3>
            <span style="cursor:pointer; font-size:20px; line-height:1;" onclick="document.getElementById('bookmarkletModal').style.display='none'"><i class="fa-solid fa-xmark"></i></span>
        </div>
        <p style="font-size:13.5px; line-height:1.5;">Mở trang <strong>WeeklyTimetable</strong> trên FAP → mở Console (F12) → dán đoạn mã bên dưới rồi nhấn Enter:</p>
        <textarea id="bookmarkletCode" readonly rows="7" style="width:100%; border-radius:8px; border:1px solid rgba(255,255,255,.15); background:rgba(0,0,0,.3); color:#38bdf8; padding:8px; font-family:monospace; font-size:12px; user-select:all;"></textarea>
        <div style="margin-top:10px; display:flex; gap:10px;">
            <button type="button" class="btn btn-accent" id="copyBookmarkletBtn"><i class="fa-solid fa-copy"></i> Sao chép mã</button>
            <span id="copyMsg" style="font-size:13px; color:var(--text-dim); align-self:center;"></span>
        </div>
    </div>
</div>

<!-- Modal thêm / sửa lịch cá nhân -->
<div class="modal-backdrop" id="eventModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:999; align-items:center; justify-content:center;">
    <div class="modal" style="background:var(--card-bg, #1e293b); color:var(--text, #fff); border-radius:12px; padding:20px; width:92%; max-width:520px; max-height:92vh; overflow-y:auto; border:1px solid rgba(255,255,255,.1);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <h3 style="margin:0;"><i class="fa-solid fa-user-clock"></i> <span id="eventModalTitle">Thêm lịch cá nhân</span></h3>
            <span style="cursor:pointer; font-size:20px; line-height:1;" id="eventCloseBtn"><i class="fa-solid fa-xmark"></i></span>
        </div>
        <input type="hidden" id="evId" value="0">
        <?php $inputStyle = 'width:100%; border-radius:8px; border:1px solid rgba(255,255,255,.2); background:rgba(0,0,0,.25); color:inherit; padding:8px 10px; font-size:13px; box-sizing:border-box;'; ?>
        <div style="display:grid; gap:10px;">
            <div>
                <label style="font-size:12px; display:block; margin-bottom:2px; color:var(--text-dim);">Tiêu đề *</label>
                <input type="text" id="evTitle" maxlength="200" style="<?= $inputStyle ?>" placeholder="VD: Học nhóm, Đi khám, Làm lab THM…">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">
                <div>
                    <label style="font-size:12px; display:block; margin-bottom:2px; color:var(--text-dim);">Ngày *</label>
                    <input type="date" id="evDate" style="<?= $inputStyle ?>">
                </div>
                <div>
                    <label style="font-size:12px; display:block; margin-bottom:2px; color:var(--text-dim);">Bắt đầu</label>
                    <input type="time" id="evStart" style="<?= $inputStyle ?>">
                </div>
                <div>
                    <label style="font-size:12px; display:block; margin-bottom:2px; color:var(--text-dim);">Kết thúc</label>
                    <input type="time" id="evEnd" style="<?= $inputStyle ?>">
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 90px; gap:10px;">
                <div>
                    <label style="font-size:12px; display:block; margin-bottom:2px; color:var(--text-dim);">Địa điểm</label>
                    <input type="text" id="evLocation" maxlength="150" style="<?= $inputStyle ?>" placeholder="VD: Thư viện, Zoom…">
                </div>
                <div>
                    <label style="font-size:12px; display:block; margin-bottom:2px; color:var(--text-dim);">Màu</label>
                    <input type="color" id="evColor" value="#8bc53f" style="width:100%; height:35px; border-radius:8px; border:1px solid rgba(255,255,255,.2); background:transparent; padding:2px;">
                </div>
            </div>
            <div>
                <label style="font-size:12px; display:block; margin-bottom:2px; color:var(--text-dim);">Ghi chú</label>
                <textarea id="evNote" rows="3" style="<?= $inputStyle ?>"></textarea>
            </div>
        </div>
        <div style="margin-top:14px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <button type="button" class="btn btn-accent" id="evSaveBtn"><i class="fa-solid fa-floppy-disk"></i> Lưu</button>
            <button type="button" class="btn" id="evDelBtn" style="display:none;"><i class="fa-solid fa-trash"></i> Xóa</button>
            <span id="evMsg" style="font-size:13px; color:var(--text-dim);"></span>
        </div>
    </div>
</div>

<!-- Modal xếp / sửa tiết cố định -->
<div class="modal-backdrop" id="slotModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:999; align-items:center; justify-content:center;">
    <div class="modal" style="background:var(--card-bg, #1e293b); color:var(--text, #fff); border-radius:12px; padding:20px; width:92%; max-width:520px; max-height:92vh; overflow-y:auto; border:1px solid rgba(255,255,255,.1);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <h3 style="margin:0;"><i class="fa-solid fa-thumbtack"></i> <span id="slotModalTitle">Xếp lịch cố định</span></h3>
            <span style="cursor:pointer; font-size:20px; line-height:1;" id="slotCloseBtn"><i class="fa-solid fa-xmark"></i></span>
        </div>
        <input type="hidden" id="slotId" value="0">
        <div style="display:grid; gap:10px;">
            <div>
                <label style="font-size:12px; display:block; margin-bottom:2px; color:var(--text-dim);">Môn học *</label>
                <select id="slotSubject" style="<?= $inputStyle ?>"></select>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">
                <div>
                    <label style="font-size:12px; display:block; margin-bottom:2px; color:var(--text-dim);">Thứ *</label>
                    <select id="slotDay" style="<?= $inputStyle ?>">
                        <option value="2">Thứ 2</option><option value="3">Thứ 3</option><option value="4">Thứ 4</option>
                        <option value="5">Thứ 5</option><option value="6">Thứ 6</option><option value="7">Thứ 7</option><option value="8">Chủ nhật</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:12px; display:block; margin-bottom:2px; color:var(--text-dim);">Slot *</label>
                    <select id="slotNo" style="<?= $inputStyle ?>"></select>
                </div>
                <div>
                    <label style="font-size:12px; display:block; margin-bottom:2px; color:var(--text-dim);">Lặp lại</label>
                    <select id="slotWeekType" style="<?= $inputStyle ?>">
                        <option value="all">Mỗi tuần</option><option value="odd">Tuần lẻ</option><option value="even">Tuần chẵn</option>
                    </select>
                </div>
            </div>
            <div>
                <label style="font-size:12px; display:block; margin-bottom:2px; color:var(--text-dim);">Phòng / Nền tảng</label>
                <input type="text" id="slotRoom" maxlength="50" style="<?= $inputStyle ?>" placeholder="VD: 415, Zoom, TryHackMe…">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                <div>
                    <label style="font-size:12px; display:block; margin-bottom:2px; color:var(--text-dim);">Áp dụng từ ngày</label>
                    <input type="date" id="slotFrom" style="<?= $inputStyle ?>">
                </div>
                <div>
                    <label style="font-size:12px; display:block; margin-bottom:2px; color:var(--text-dim);">Đến ngày</label>
                    <input type="date" id="slotTo" style="<?= $inputStyle ?>">
                </div>
            </div>
            <div style="font-size:12px; color:var(--text-dim);">Để trống 2 ô ngày = áp dụng mãi. Đặt khoảng ngày để giới hạn trong 1 kỳ. Nếu ô đã có tiết khác trùng kiểu tuần và khoảng ngày thì tiết cũ sẽ bị thay thế.</div>
        </div>
        <div style="margin-top:14px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <button type="button" class="btn btn-accent" id="slotSaveBtn"><i class="fa-solid fa-floppy-disk"></i> Lưu</button>
            <button type="button" class="btn" id="slotDelBtn" style="display:none;"><i class="fa-solid fa-trash"></i> Xóa</button>
            <span id="slotMsg" style="font-size:13px; color:var(--text-dim);"></span>
        </div>
    </div>
</div>

<!-- Gửi Telegram -->
<div class="card" style="margin-bottom:16px;">
    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
        <strong><i class="fa-brands fa-telegram" style="color:#29a9eb;"></i> Gửi lịch qua Telegram</strong>
        <button type="button" class="btn" id="tgTodayBtn"><i class="fa-solid fa-paper-plane"></i> Lịch hôm nay</button>
        <button type="button" class="btn" id="tgWeekBtn"><i class="fa-solid fa-paper-plane"></i> Lịch 7 ngày tới</button>
        <span id="tgMsg" style="font-size:13px; color:var(--text-dim);"></span>
    </div>
</div>

<script>
(function () {
    var btn = document.getElementById('fapSyncBtn');
    var token = <?= json_encode($fap['token']) ?>;
    var qlhtApiUrl = <?= json_encode($apiBase . '/api/fap_import.php') ?>;

    // Hiển thị thông báo kèm icon Font Awesome (an toàn, không dùng innerHTML với dữ liệu ngoài)
    var MSG_ICONS = {
        ok:   'fa-solid fa-circle-check',
        err:  'fa-solid fa-circle-xmark',
        load: 'fa-solid fa-spinner fa-spin'
    };
    function setMsg(el, type, text) {
        el.textContent = '';
        var i = document.createElement('i');
        i.className = MSG_ICONS[type];
        el.appendChild(i);
        el.appendChild(document.createTextNode(' ' + text));
    }

    var jsCode = `(function(){
    var token = "${token}";
    var qlhtUrl = "${qlhtApiUrl}";
    var tables = document.querySelectorAll('table');
    if(!tables.length){ alert('Không tìm thấy bảng lịch học trên trang FAP này!'); return; }
    var dates = [];
    var ths = tables[0].querySelectorAll('tr:first-child th, tr:first-child td');
    var curYear = (new Date()).getFullYear();
    ths.forEach(function(th, idx){
        var m = th.textContent.match(/(\\d{1,2})\\/(\\d{1,2})(?:\\/(\\d{4}))?/);
        if(m){
            var d = m[1].padStart(2, '0');
            var mo = m[2].padStart(2, '0');
            var y = m[3] || curYear;
            dates[idx] = y + '-' + mo + '-' + d;
        }
    });
    var sessions = [];
    var rows = tables[0].querySelectorAll('tr');
    var curSlot = 0;
    rows.forEach(function(tr, rIdx){
        if(rIdx === 0) return;
        var cells = tr.querySelectorAll('td');
        if(!cells.length) return;
        var sm = cells[0].textContent.match(/(?:Slot|Ca)\\s*(\\d+)/i) || cells[0].textContent.match(/^(\\d+)$/);
        if(sm) curSlot = parseInt(sm[1], 10); else curSlot++;
        for(var i=1; i<cells.length; i++){
            var td = cells[i];
            var txt = td.textContent.trim();
            if(!txt || txt === '-' || txt === '—') continue;
            var dt = dates[i];
            if(!dt) continue;
            var a = td.querySelector('a');
            var code = a ? a.textContent.trim() : '';
            if(!code){
                var cm = txt.match(/\\b([A-Z]{3,4}\\d{3}[a-zA-Z]?)\\b/);
                if(cm) code = cm[1];
            }
            if(!code) continue;
            var rm = txt.match(/(?:at|phòng|room)\\s*([A-Za-z0-9_-]+)/i);
            var room = rm ? rm[1] : '';
            var tm = txt.match(/(\\d{1,2}:\\d{2})\\s*[-–]\\s*(\\d{1,2}:\\d{2})/);
            var start = tm ? tm[1] : '';
            var end = tm ? tm[2] : '';
            var status = '';
            if(/attended|đã học/i.test(txt)) status = 'attended';
            else if(/absent|vắng/i.test(txt)) status = 'absent';
            sessions.push({ date: dt, slot: curSlot, code: code, room: room, start: start, end: end, status: status });
        }
    });
    if(!sessions.length){ alert('Không bóc tách được buổi học nào!'); return; }
    fetch(qlhtUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-QLHT-Token': token },
        body: JSON.stringify({ sessions: sessions })
    }).then(function(r){ return r.json(); }).then(function(d){
        if(d.ok){ alert('Đã đồng bộ ' + sessions.length + ' buổi học từ FAP thành công!'); location.reload(); }
        else { alert('Lỗi: ' + (d.error || 'Không đồng bộ được')); }
    }).catch(function(e){ alert('Lỗi kết nối tới QLHT: ' + e); });
})();`;

    document.getElementById('bookmarkletCode').value = jsCode;

    document.getElementById('fapCookieModalBtn').addEventListener('click', function () {
        document.getElementById('cookieModal').style.display = 'flex';
    });
    document.getElementById('fapBookmarkletBtn').addEventListener('click', function () {
        document.getElementById('bookmarkletModal').style.display = 'flex';
    });
    document.getElementById('copyBookmarkletBtn').addEventListener('click', function () {
        var el = document.getElementById('bookmarkletCode');
        el.select();
        navigator.clipboard.writeText(el.value).then(function () {
            setMsg(document.getElementById('copyMsg'), 'ok', 'Đã chép!');
            setTimeout(function () { document.getElementById('copyMsg').textContent = ''; }, 2000);
        });
    });

    // Thao tác nút từ hôm nay & xóa
    document.getElementById('setTodayBtn').addEventListener('click', function () {
        var today = new Date();
        var y = today.getFullYear();
        var m = String(today.getMonth() + 1).padStart(2, '0');
        var d = String(today.getDate()).padStart(2, '0');
        document.getElementById('syncFromDateInput').value = y + '-' + m + '-' + d;
        document.getElementById('syncToDateInput').value = '';
        updateDateDisplay();
    });
    document.getElementById('clearDateBtn').addEventListener('click', function () {
        document.getElementById('syncFromDateInput').value = '';
        document.getElementById('syncToDateInput').value = '';
        updateDateDisplay();
    });
    document.getElementById('syncFromDateInput').addEventListener('change', updateDateDisplay);
    document.getElementById('syncToDateInput').addEventListener('change', updateDateDisplay);

    function updateDateDisplay() {
        var fromVal = document.getElementById('syncFromDateInput').value;
        var toVal   = document.getElementById('syncToDateInput').value;
        var el = document.getElementById('syncDateRangeDisplay');

        if (fromVal || toVal) {
            var fTxt = fromVal ? fromVal.split('-').reverse().join('/') : 'đầu';
            var tTxt = toVal ? toVal.split('-').reverse().join('/') : 'vô tận';
            el.innerHTML = '<i class="fa-solid fa-circle-check" style="color:#22c55e;"></i> Sẽ lấy lịch từ <strong>' + fTxt + '</strong> đến <strong>' + tTxt + '</strong>';
        } else {
            el.innerHTML = '<i class="fa-solid fa-circle-info" style="color:#38bdf8;"></i> Chưa cấu hình giới hạn ngày — sẽ lấy toàn bộ lịch.';
        }
    }

    // Lưu cấu hình từ ngày -> đến ngày & cookie
    document.getElementById('saveConfigBtn').addEventListener('click', function () {
        var fromDate = document.getElementById('syncFromDateInput').value.trim();
        var toDate   = document.getElementById('syncToDateInput').value.trim();
        var cookie   = document.getElementById('cookieInput').value.trim();
        var msg = document.getElementById('cookieModalMsg');
        setMsg(msg, 'load', 'Đang lưu...');

        var payload = { sync_from_date: fromDate, sync_to_date: toDate };
        if (cookie) payload.cookie = cookie;

        fetch('api/fap_cookie_sync.php?action=save_config', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).then(function(r){ return r.json(); }).then(function(d){
            if (d.ok) {
                setMsg(msg, 'ok', d.message);
                updateDateDisplay();
            } else {
                setMsg(msg, 'err', d.error || 'Lỗi không xác định');
            }
        }).catch(function(e){ setMsg(msg, 'err', 'Lỗi kết nối: ' + e); });
    });

    document.getElementById('crawlServerBtn').addEventListener('click', function () {
        var msg = document.getElementById('cookieModalMsg');
        setMsg(msg, 'load', 'Đang gửi yêu cầu tới FAP...');
        fetch('api/fap_cookie_sync.php?action=crawl_server', {method: 'POST'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.ok) {
                    setMsg(msg, 'ok', 'Thêm ' + (d.added||0) + ' buổi, cập nhật ' + (d.updated||0) + ' buổi.');
                    setTimeout(function(){ location.reload(); }, 1500);
                } else {
                    setMsg(msg, 'err', d.error || 'Thất bại');
                }
            }).catch(function(e){ setMsg(msg, 'err', 'Lỗi: ' + e); });
    });

    document.getElementById('importHtmlBtn').addEventListener('click', function () {
        var html = document.getElementById('fapHtmlInput').value.trim();
        if (!html) { alert('Vui lòng dán mã HTML FAP!'); return; }
        fetch('api/fap_cookie_sync.php?action=import_html', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({html: html})
        }).then(function(r){ return r.json(); }).then(function(d){
            if (d.ok) {
                alert('Thêm ' + (d.added||0) + ' buổi, cập nhật ' + (d.updated||0) + ' buổi.');
                location.reload();
            } else {
                alert('Lỗi: ' + (d.error || 'Không bóc tách được'));
            }
        }).catch(function(e){ alert('Lỗi: ' + e); });
    });

    btn.addEventListener('click', function () {
        location.href = 'schedule.php?fap_sync=1';
    });
        // ══════════ NHẬN DỮ LIỆU TỪ AUTOMA (#d=...) VÀ LƯU ══════════
    var IMPORTING = <?= $importing ? 'true' : 'false' ?>;
    if (IMPORTING) {
        var st  = document.getElementById('fapSyncStatus');
        var raw = (location.hash || '').replace(/^#d=/, '');
        var sessions = null;

        if (!raw) {
            setMsg(st, 'err', 'Không nhận được dữ liệu từ Automa (thiếu #d=...). Hãy chạy lại workflow.');
        } else {
            try { sessions = JSON.parse(decodeURIComponent(raw)); }
            catch (e) { setMsg(st, 'err', 'Dữ liệu từ Automa không hợp lệ: ' + e); }
        }

        if (sessions) {
            setMsg(st, 'load', 'Đang lưu ' + sessions.length + ' buổi học từ FAP…');
            var ctrl  = new AbortController();
            var timer = setTimeout(function () { ctrl.abort(); }, 20000);   // tối đa 20 giây

            fetch('api/fap_import.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({sessions: sessions}),
                signal: ctrl.signal
            }).then(function (r) { return r.text(); }).then(function (t) {
                var d;
                try { d = JSON.parse(t); }
                catch (e) { throw new Error('Server trả về không phải JSON: ' + t.slice(0, 200)); }
                if (d.ok) {
                    setMsg(st, 'ok', 'Đã lưu: thêm ' + (d.added || 0) + ', cập nhật ' + (d.updated || 0) + ' buổi. Đang tải lại…');
                    history.replaceState(null, '', 'schedule.php');
                    setTimeout(function () { location.href = 'schedule.php'; }, 1200);
                } else {
                    setMsg(st, 'err', d.error || 'Lưu thất bại');
                }
            }).catch(function (e) {
                setMsg(st, 'err', 'Lỗi: ' + (e.name === 'AbortError' ? 'quá 20 giây không phản hồi' : e.message));
            }).finally(function () { clearTimeout(timer); });
        }
    }

    // ══════════ LỊCH TUẦN + XẾP LỊCH CỐ ĐỊNH ══════════
    function byId(id) { return document.getElementById(id); }
    var API = 'api/schedule.php';
    var TODAY = <?= json_encode($today) ?>;
    var SUBJECTS = <?= json_encode($subjects, JSON_UNESCAPED_UNICODE) ?>;
    var SLOT_TIMES = <?= json_encode($slotTimes) ?>;
    var DOW = {2: 'Thứ 2', 3: 'Thứ 3', 4: 'Thứ 4', 5: 'Thứ 5', 6: 'Thứ 6', 7: 'Thứ 7', 8: 'CN'};
    var weekAnchor = TODAY, weekData = null, drag = null;

    function el(tag, cls, text) {
        var e = document.createElement(tag);
        if (cls) e.className = cls;
        if (text != null) e.textContent = text;
        return e;
    }
    function pad(n) { return String(n).padStart(2, '0'); }
    function fmt(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function parseD(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
    function addDays(s, n) { var d = parseD(s); d.setDate(d.getDate() + n); return fmt(d); }
    function dm(s) { var p = s.split('-'); return p[2] + '/' + p[1]; }
    function slotTime(n) { var t = SLOT_TIMES[n]; return t ? t[0] + '–' + t[1] : ''; }
    function call(method, body) {
        return fetch(API, {method: method, headers: {'Content-Type': 'application/json'}, body: JSON.stringify(body)})
            .then(function (r) { return r.json(); });
    }

    // ── Tải + vẽ tuần ──
    function loadWeek() {
        setMsg(byId('wkMsg'), 'load', 'Đang tải…');
        fetch(API + '?week=' + encodeURIComponent(weekAnchor))
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.error) { setMsg(byId('wkMsg'), 'err', d.error); return; }
                byId('wkMsg').textContent = '';
                weekData = d;
                renderWeek();
            })
            .catch(function (e) { setMsg(byId('wkMsg'), 'err', 'Lỗi kết nối: ' + e); });
    }

    function eventChip(it) {
        var c = el('div', 'blk blk-event');
        c.style.borderLeftColor = it.color || '#8bc53f';
        c.appendChild(el('div', 'blk-code', (it.start ? it.start + (it.end ? '–' + it.end : '') + ' ' : '') + it.name));
        if (it.room) c.appendChild(el('div', 'blk-meta', it.room));
        c.addEventListener('click', function (e) { e.stopPropagation(); openEventModal(it.ev); });
        return c;
    }

    function blockEl(it) {
        var b = el('div', 'blk blk-' + it.kind);
        b.style.borderLeftColor = it.color || '#8bc53f';
        var top = el('div', 'blk-code', it.code);
        if (it.kind === 'fap') {
            top.appendChild(el('span', 'tag', 'FAP'));
            if (it.status === 'attended' || it.status === 'absent') {
                var ic = el('i', it.status === 'attended' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark');
                ic.style.cssText = 'margin-left:4px;color:' + (it.status === 'attended' ? '#22c55e' : '#ef4444');
                top.appendChild(ic);
            }
            b.addEventListener('click', function (e) { e.stopPropagation(); });
        } else {
            top.appendChild(el('span', 'tag', 'Cố định'));
            if (it.week_type !== 'all') top.appendChild(el('span', 'tag', it.week_type === 'odd' ? 'Tuần lẻ' : 'Tuần chẵn'));
            b.draggable = true;
            b.addEventListener('dragstart', function (e) {
                drag = {t: 'move', id: it.id};
                e.dataTransfer.setData('text/plain', 'move');
                e.dataTransfer.effectAllowed = 'move';
            });
            b.addEventListener('dragend', function () { drag = null; });
            b.addEventListener('click', function (e) { e.stopPropagation(); openSlotModal(it); });
        }
        b.appendChild(top);
        if (it.name) b.appendChild(el('div', 'blk-name', it.name));
        var time = (it.kind === 'fap' && it.start) ? it.start + '–' + it.end : slotTime(it.slot);
        var meta = [time, it.room].filter(Boolean).join(' · ');
        if (meta) b.appendChild(el('div', 'blk-meta', meta));
        b.title = it.code + (it.name ? ' – ' + it.name : '');
        return b;
    }

    function renderWeek() {
        var d = weekData, tbl = byId('wkTable');
        tbl.textContent = '';
        byId('wkTitle').textContent = 'Tuần ' + d.week_no + ' · ' + dm(d.week_start) + ' – ' + dm(d.week_end) + '/' + d.week_end.slice(0, 4);

        var dates = [], i;
        for (i = 0; i < 7; i++) dates.push(addDays(d.week_start, i));

        var cells = {}, events = {}, slots = {};
        for (i = 1; i <= 8; i++) slots[i] = 1;
        d.items.forEach(function (it) {
            if (it.kind === 'event') { (events[it.date] = events[it.date] || []).push(it); return; }
            slots[it.slot] = 1;
            var k = it.date + '#' + it.slot;
            (cells[k] = cells[k] || []).push(it);
        });
        var slotList = Object.keys(slots).map(Number).sort(function (a, b) { return a - b; });

        var thead = el('thead'), hr = el('tr');
        hr.appendChild(el('th'));
        dates.forEach(function (dt, idx) {
            var th = el('th', dt === TODAY ? 'today' : '');
            th.appendChild(el('div', '', DOW[idx + 2]));
            th.appendChild(el('div', 'wk-date', dm(dt)));
            hr.appendChild(th);
        });
        thead.appendChild(hr);
        tbl.appendChild(thead);

        var tbody = el('tbody');

        // Hàng lịch cá nhân
        var evRow = el('tr'), evTh = el('th');
        evTh.appendChild(el('div', 'wk-slotn', 'Cá nhân'));
        evRow.appendChild(evTh);
        dates.forEach(function (dt) {
            var td = el('td', 'wk-cell' + (dt === TODAY ? ' today' : ''));
            (events[dt] || []).sort(function (a, b) { return a.start.localeCompare(b.start); })
                .forEach(function (it) { td.appendChild(eventChip(it)); });
            td.addEventListener('click', function () { openEventModal({event_date: dt}); });
            evRow.appendChild(td);
        });
        tbody.appendChild(evRow);

        // Các hàng slot
        slotList.forEach(function (n) {
            var tr = el('tr'), th = el('th');
            th.appendChild(el('div', 'wk-slotn', 'Slot ' + n));
            th.appendChild(el('div', 'wk-time', slotTime(n)));
            tr.appendChild(th);
            dates.forEach(function (dt, idx) {
                var day = idx + 2;
                var td = el('td', 'wk-cell' + (dt === TODAY ? ' today' : ''));
                (cells[dt + '#' + n] || []).forEach(function (it) { td.appendChild(blockEl(it)); });
                td.addEventListener('click', function () { openSlotModal({day_of_week: day, slot_number: n}); });
                td.addEventListener('dragover', function (e) { if (drag) { e.preventDefault(); td.classList.add('dragover'); } });
                td.addEventListener('dragleave', function () { td.classList.remove('dragover'); });
                td.addEventListener('drop', function (e) { e.preventDefault(); td.classList.remove('dragover'); dropOn(day, n); });
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        tbl.appendChild(tbody);
    }

    function dropOn(day, slot) {
        var p = drag; drag = null;
        if (!p) return;
        var req = p.t === 'move'
            ? call('PUT',  {schedule_id: p.id, day_of_week: day, slot_number: slot})
            : call('POST', {subject_id: p.id, day_of_week: day, slot_number: slot});
        req.then(function (r) { if (r.error) alert(r.error); loadWeek(); })
           .catch(function (e) { alert('Lỗi kết nối: ' + e); });
    }

    // ── Điều hướng tuần ──
    byId('wkPrev').addEventListener('click', function () { weekAnchor = addDays(weekData.week_start, -7); loadWeek(); });
    byId('wkNext').addEventListener('click', function () { weekAnchor = addDays(weekData.week_start, 7); loadWeek(); });
    byId('wkToday').addEventListener('click', function () { weekAnchor = TODAY; loadWeek(); });
    byId('wkPick').addEventListener('change', function () { if (this.value) { weekAnchor = this.value; loadWeek(); } });

    // ── Kho môn học ──
    function renderBank() {
        var f = byId('bankFilter').value, box = byId('bank');
        box.textContent = '';
        SUBJECTS.filter(function (s) {
            if (f === 'all') return true;
            if (f === 'done') return s.status === 'completed' || s.status === 'exempted';
            return s.status === f;
        }).forEach(function (s) {
            var c = el('span', 'subject-chip bank-chip', s.subject_code);
            c.style.background = s.color || '#334155';
            c.title = s.subject_name;
            c.draggable = true;
            c.addEventListener('dragstart', function (e) {
                drag = {t: 'subject', id: +s.id};
                e.dataTransfer.setData('text/plain', 'subject');
                e.dataTransfer.effectAllowed = 'copy';
            });
            c.addEventListener('dragend', function () { drag = null; });
            c.addEventListener('click', function () { openSlotModal({subject_id: +s.id}); });
            box.appendChild(c);
        });
        if (!box.firstChild) box.appendChild(el('span', '', 'Không có môn nào trong nhóm này.'));
    }
    byId('bankFilter').addEventListener('change', renderBank);

    // ── Modal xếp / sửa tiết cố định ──
    var slotModal = byId('slotModal'), slotMsg = byId('slotMsg');

    function fillSlotForm() {
        var sel = byId('slotSubject');
        sel.appendChild(new Option('— Chọn môn —', ''));
        [['ongoing', 'Đang học'], ['not_started', 'Chưa học'], ['completed', 'Đã học'], ['exempted', 'Miễn giảm']].forEach(function (g) {
            var og = document.createElement('optgroup');
            og.label = g[1];
            SUBJECTS.forEach(function (s) {
                if (s.status === g[0]) og.appendChild(new Option(s.subject_code + ' — ' + s.subject_name, s.id));
            });
            if (og.children.length) sel.appendChild(og);
        });
        var no = byId('slotNo');
        for (var n = 1; n <= 12; n++) no.appendChild(new Option('Slot ' + n + (SLOT_TIMES[n] ? ' (' + slotTime(n) + ')' : ''), n));
    }

    function openSlotModal(s) {
        s = s || {};
        byId('slotModalTitle').textContent = s.id ? 'Sửa lịch cố định' : 'Xếp lịch cố định';
        byId('slotId').value = s.id || 0;
        byId('slotSubject').value = s.subject_id || '';
        byId('slotDay').value = s.day_of_week || 2;
        byId('slotNo').value = s.slot_number || s.slot || 1;
        byId('slotRoom').value = s.room || '';
        byId('slotWeekType').value = s.week_type || 'all';
        byId('slotFrom').value = s.start_date || '';
        byId('slotTo').value = s.end_date || '';
        byId('slotDelBtn').style.display = s.id ? '' : 'none';
        slotMsg.textContent = '';
        slotModal.style.display = 'flex';
    }
    byId('addSlotBtn').addEventListener('click', function () { openSlotModal(); });
    byId('slotCloseBtn').addEventListener('click', function () { slotModal.style.display = 'none'; });

    byId('slotSaveBtn').addEventListener('click', function () {
        var id = parseInt(byId('slotId').value, 10) || 0;
        var body = {
            subject_id: parseInt(byId('slotSubject').value, 10) || 0,
            day_of_week: parseInt(byId('slotDay').value, 10),
            slot_number: parseInt(byId('slotNo').value, 10),
            room: byId('slotRoom').value,
            week_type: byId('slotWeekType').value,
            start_date: byId('slotFrom').value,
            end_date: byId('slotTo').value
        };
        if (id) body.schedule_id = id;
        setMsg(slotMsg, 'load', 'Đang lưu...');
        call(id ? 'PUT' : 'POST', body).then(function (d) {
            if (d.error) { setMsg(slotMsg, 'err', d.error); return; }
            slotModal.style.display = 'none';
            loadWeek();
        }).catch(function (e) { setMsg(slotMsg, 'err', 'Lỗi kết nối: ' + e); });
    });

    byId('slotDelBtn').addEventListener('click', function () {
        if (!confirm('Xóa tiết cố định này?')) return;
        call('DELETE', {schedule_id: parseInt(byId('slotId').value, 10)}).then(function (d) {
            if (d.error) { setMsg(slotMsg, 'err', d.error); return; }
            slotModal.style.display = 'none';
            loadWeek();
        }).catch(function (e) { setMsg(slotMsg, 'err', 'Lỗi kết nối: ' + e); });
    });

    // ── Lịch cá nhân ──
    var evModal = byId('eventModal'), evMsg = byId('evMsg');

    function openEventModal(ev) {
        ev = ev || {};
        byId('eventModalTitle').textContent = ev.id ? 'Sửa lịch cá nhân' : 'Thêm lịch cá nhân';
        byId('evId').value       = ev.id || 0;
        byId('evTitle').value    = ev.title || '';
        byId('evDate').value     = ev.event_date || TODAY;
        byId('evStart').value    = ev.start_time || '';
        byId('evEnd').value      = ev.end_time || '';
        byId('evLocation').value = ev.location || '';
        byId('evNote').value     = ev.note || '';
        byId('evColor').value    = ev.color || '#8bc53f';
        byId('evDelBtn').style.display = ev.id ? '' : 'none';
        evMsg.textContent = '';
        evModal.style.display = 'flex';
        byId('evTitle').focus();
    }
    byId('addEventBtn').addEventListener('click', function () { openEventModal({event_date: weekData ? weekData.week_start : TODAY}); });
    byId('eventCloseBtn').addEventListener('click', function () { evModal.style.display = 'none'; });

    byId('evDelBtn').addEventListener('click', function () {
        if (!confirm('Xóa lịch cá nhân này?')) return;
        fetch('api/personal_events.php?action=delete', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: parseInt(byId('evId').value, 10)})
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (d.ok) { evModal.style.display = 'none'; loadWeek(); }
            else setMsg(evMsg, 'err', d.error || 'Không xóa được');
        }).catch(function (e) { setMsg(evMsg, 'err', 'Lỗi kết nối: ' + e); });
    });

    byId('evSaveBtn').addEventListener('click', function () {
        setMsg(evMsg, 'load', 'Đang lưu...');
        fetch('api/personal_events.php?action=save', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                id:         parseInt(byId('evId').value, 10) || 0,
                title:      byId('evTitle').value,
                event_date: byId('evDate').value,
                start_time: byId('evStart').value,
                end_time:   byId('evEnd').value,
                location:   byId('evLocation').value,
                note:       byId('evNote').value,
                color:      byId('evColor').value
            })
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (d.ok) { evModal.style.display = 'none'; loadWeek(); }
            else setMsg(evMsg, 'err', d.error || 'Lỗi không xác định');
        }).catch(function (e) { setMsg(evMsg, 'err', 'Lỗi kết nối: ' + e); });
    });

    fillSlotForm();
    renderBank();
    loadWeek();


    // ── Gửi Telegram ──
    var tgMsg = document.getElementById('tgMsg');
    function sendTelegram(scope) {
        setMsg(tgMsg, 'load', 'Đang gửi...');
        fetch('api/telegram_send.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({scope: scope})
        }).then(function (r) { return r.json(); }).then(function (d) {
            setMsg(tgMsg, d.ok ? 'ok' : 'err', d.ok ? d.message : (d.error || 'Gửi thất bại'));
        }).catch(function (e) { setMsg(tgMsg, 'err', 'Lỗi kết nối: ' + e); });
    }
    document.getElementById('tgTodayBtn').addEventListener('click', function () { sendTelegram('today'); });
    document.getElementById('tgWeekBtn').addEventListener('click', function () { sendTelegram('week'); });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>