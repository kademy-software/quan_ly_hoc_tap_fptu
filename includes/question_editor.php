<?php
/**
 * Hộp thoại sửa nội dung câu hỏi dùng chung.
 * include file này 1 lần trong trang, sau đó gọi trong JS:
 *
 *   QEditor.open(question, 'câu 3', function (updated) { ... });
 *
 * - question: object câu hỏi (cần id, question_text, opt_a..opt_d, correct_opt, explanation)
 * - updated : dữ liệu mới server đã lưu (cùng các trường trên)
 * Việc lưu được gửi tới quiz_take.php (action=update_question).
 */
?>
<style>
.qe-modal { max-width: 640px; }
.qe-modal textarea { resize: vertical; min-height: 80px; }
.qe-opt { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.qe-opt input[type=radio] { width: auto; accent-color: var(--accent); cursor: pointer; flex-shrink: 0; }
.qe-key { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 6px; background: var(--bg-panel-2); border: 1px solid var(--border); color: var(--text-dim); font-size: 12px; font-weight: bold; flex-shrink: 0; }
.qe-note { font-size: 12px; color: var(--text-dim); margin: 4px 0 14px; }
.qe-error { display: none; margin-bottom: 12px; }
.qe-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px; }
</style>

<div class="modal-backdrop" id="qeModal">
    <div class="modal qe-modal">
        <div class="card-header">
            <h3 style="margin:0;">Sửa câu hỏi <span id="qeLabel" style="color:var(--text-dim); font-weight:500;"></span></h3>
            <span class="modal-close" id="qeClose" title="Đóng">&times;</span>
        </div>

        <div class="flash error qe-error" id="qeError"></div>

        <div class="form-row">
            <label for="qeQuestion">Nội dung câu hỏi</label>
            <textarea id="qeQuestion" rows="3"></textarea>
        </div>

        <label>Các đáp án <span style="font-weight:400;">(chọn nút tròn ở đáp án đúng)</span></label>
        <?php foreach (['A', 'B', 'C', 'D'] as $L): ?>
        <div class="qe-opt">
            <input type="radio" name="qeCorrect" value="<?= $L ?>" title="Đáp án đúng">
            <span class="qe-key"><?= $L ?></span>
            <input type="text" id="qeOpt<?= $L ?>" placeholder="Đáp án <?= $L ?><?= $L > 'B' ? ' (có thể để trống)' : '' ?>">
        </div>
        <?php endforeach; ?>
        <div class="qe-note">Cần ít nhất đáp án A và B. Đáp án để trống sẽ không hiển thị khi làm bài.</div>

        <div class="form-row">
            <label for="qeExplanation">Giải thích (tùy chọn)</label>
            <textarea id="qeExplanation" rows="2"></textarea>
        </div>

        <div class="qe-actions">
            <button type="button" class="btn btn-outline" id="qeCancel">Hủy</button>
            <button type="button" class="btn btn-accent" id="qeSave">Lưu thay đổi</button>
        </div>
    </div>
</div>

<script>
const QEditor = (function () {
    const LETTERS = ['A', 'B', 'C', 'D'];
    const $ = id => document.getElementById(id);
    const modal = $('qeModal');
    const el = {
        label: $('qeLabel'),
        question: $('qeQuestion'),
        explanation: $('qeExplanation'),
        error: $('qeError'),
        save: $('qeSave'),
        opts: { A: $('qeOptA'), B: $('qeOptB'), C: $('qeOptC'), D: $('qeOptD') }
    };
    const URL = 'quiz_take.php';
    let current = null;
    let onSaved = null;

    const isOpen = () => modal.classList.contains('open');

    function showError(msg) {
        el.error.textContent = msg;
        el.error.style.display = 'block';
    }

    function open(q, label, callback) {
        if (!q) return;
        current = q;
        onSaved = callback || null;

        el.label.textContent = label ? '(' + label + ')' : '';
        el.question.value = q.question_text || '';
        LETTERS.forEach(L => { el.opts[L].value = q['opt_' + L.toLowerCase()] || ''; });
        el.explanation.value = q.explanation || '';
        document.querySelectorAll('input[name="qeCorrect"]').forEach(r => {
            r.checked = (r.value === q.correct_opt);
        });
        el.error.style.display = 'none';
        modal.classList.add('open');
        el.question.focus();
    }

    function close() {
        modal.classList.remove('open');
        current = null;
        onSaved = null;
    }

    async function save() {
        if (!current) return;
        const checked = document.querySelector('input[name="qeCorrect"]:checked');
        const question = el.question.value.trim();

        // Kiểm tra nhanh phía trình duyệt (server vẫn kiểm tra lại)
        if (!question) return showError('Nội dung câu hỏi không được để trống.');
        if (!el.opts.A.value.trim() || !el.opts.B.value.trim()) return showError('Cần ít nhất 2 đáp án (A và B).');
        if (!checked) return showError('Hãy chọn đáp án đúng.');
        if (!el.opts[checked.value].value.trim()) return showError('Đáp án đúng không được để trống.');

        const fd = new FormData();
        fd.append('action', 'update_question');
        fd.append('id', current.id);
        fd.append('question_text', question);
        LETTERS.forEach(L => fd.append('opt_' + L.toLowerCase(), el.opts[L].value));
        fd.append('correct_opt', checked.value);
        fd.append('explanation', el.explanation.value);

        el.save.disabled = true;
        el.save.textContent = 'Đang lưu...';
        try {
            const res = await fetch(URL, { method: 'POST', body: fd });
            const json = await res.json().catch(() => null);
            if (!res.ok || !json || !json.ok) {
                showError((json && json.error) || 'Không lưu được, vui lòng thử lại.');
                return;
            }
            const cb = onSaved;
            close();
            if (cb) cb(json.question);
        } catch (err) {
            showError('Lỗi kết nối, vui lòng thử lại.');
        } finally {
            el.save.disabled = false;
            el.save.textContent = 'Lưu thay đổi';
        }
    }

    $('qeClose').onclick = close;
    $('qeCancel').onclick = close;
    el.save.onclick = save;
    modal.addEventListener('mousedown', e => { if (e.target === modal) close(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && isOpen()) close(); });
    Object.values(el.opts).forEach(inp => inp.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); save(); }
    }));

    return { open, close, isOpen };
})();
</script>