<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
$db = getDB();
$sid = $_SESSION['student_id'];

// Xử lý Thêm / Xóa Payload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $stmt = $db->prepare('INSERT INTO anm_payloads (student_id, os_target, tool_name, command_syntax, description) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$sid, $_POST['os_target'], $_POST['tool_name'], $_POST['command_syntax'], $_POST['description']]);
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $db->prepare('DELETE FROM anm_payloads WHERE id = ? AND student_id = ?');
        $stmt->execute([$_POST['id'], $sid]);
    }
    header('Location: payloads.php');
    exit;
}

// Lấy danh sách Payloads
$stmt = $db->prepare('SELECT * FROM anm_payloads WHERE student_id = ? ORDER BY tool_name ASC, id DESC');
$stmt->execute([$sid]);
$payloads = $stmt->fetchAll();

$pageTitle = 'Payloads & Commands';
$activePage = 'payloads';
include __DIR__ . '/includes/header.php';
?>

<div class="card-header">
    <h1 style="margin:0; color: var(--blue);">💻 Ngân hàng Payload & Command</h1>
    <button class="btn btn-accent" onclick="document.getElementById('payloadModal').classList.add('open')">+ Thêm Payload</button>
</div>
<p style="color:var(--text-dim); margin-top:-8px;">Tra cứu nhanh các câu lệnh CLI, Reverse Shells, và sử dụng công cụ Encode/Decode.</p>

<!-- Khối Công cụ Quick Encode / Decode -->
<div class="card" style="margin-top:20px; border-top: 4px solid var(--blue);">
    <div class="card-header"><h3 style="margin:0;">Quick Encode / Decode (Local)</h3></div>
    <div class="grid grid-2" style="gap: 16px;">
        <div>
            <label>Đầu vào (Input)</label>
            <textarea id="enc_input" rows="4" placeholder="Nhập chuỗi cần xử lý..." oninput="processConversion()"></textarea>
        </div>
        <div>
            <label>Đầu ra (Output)</label>
            <textarea id="enc_output" rows="4" placeholder="Kết quả sẽ hiển thị ở đây..." readonly style="background: var(--bg);"></textarea>
        </div>
    </div>
    <div style="margin-top: 12px; display: flex; gap: 10px; align-items: center;">
        <select id="enc_mode" onchange="processConversion()" style="width: auto;">
            <option value="b64enc">Base64 Encode</option>
            <option value="b64dec">Base64 Decode</option>
            <option value="urlenc">URL Encode</option>
            <option value="urldec">URL Decode</option>
            <option value="hexenc">Hex Encode</option>
            <option value="hexdec">Hex Decode</option>
            <option value="vnienc">VNI Encode (Caesar +3)</option>
            <option value="vnidec">VNI Decode (Caesar +3)</option>
        </select>
        <button class="btn btn-outline btn-sm" onclick="copyOutput()">Copy Output</button>
    </div>
</div>

<!-- Bộ lọc và Danh sách Payloads -->
<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <h3 style="margin:0;">Danh sách Lệnh</h3>
        <input type="text" id="searchInput" placeholder="Tìm kiếm công cụ hoặc lệnh..." onkeyup="filterPayloads()" style="width: 250px; padding: 6px 12px;">
    </div>
    
    <div class="grid grid-2" id="payloadContainer" style="gap: 16px;">
        <?php foreach ($payloads as $p): 
            $badgeColor = 'badge-gray';
            if ($p['os_target'] === 'Linux') $badgeColor = 'badge-orange';
            if ($p['os_target'] === 'Windows') $badgeColor = 'badge-blue';
        ?>
        <div class="card payload-item" style="background: var(--bg); border: 1px solid var(--border); padding: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <div>
                    <strong style="color: var(--accent); font-size: 16px;" class="p-tool"><?= htmlspecialchars($p['tool_name']) ?></strong>
                    <span class="badge <?= $badgeColor ?>" style="margin-left: 8px;"><?= htmlspecialchars($p['os_target']) ?></span>
                </div>
                <form method="post" onsubmit="return confirm('Xóa payload này?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="icon-btn" title="Xóa" aria-label="Xóa payload"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                </form>
            </div>
            
            <p style="font-size: 13px; color: var(--text-dim); margin-top:0; margin-bottom: 12px;" class="p-desc">
                <?= htmlspecialchars($p['description']) ?>
            </p>
            
            <div style="position: relative;">
                <textarea id="cmd-<?= $p['id'] ?>" class="p-cmd" readonly style="font-family: monospace; font-size: 13px; color: var(--text); background: var(--bg-panel-2); border: 1px solid var(--border); width: 100%; padding: 10px; border-radius: 6px; resize: none; overflow: hidden;" oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"><?= htmlspecialchars($p['command_syntax']) ?></textarea>
                <button class="btn btn-accent btn-sm" style="position: absolute; top: 6px; right: 6px; opacity: 0.8;" onclick="copyCommand('cmd-<?= $p['id'] ?>')">Copy</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if (!$payloads): ?><div class="empty-state">Chưa có payload nào được lưu.</div><?php endif; ?>
</div>

<!-- Modal Thêm Payload -->
<div class="modal-backdrop" id="payloadModal">
    <div class="modal">
        <div class="card-header">
            <h2>Thêm Payload mới</h2>
            <span class="modal-close" onclick="document.getElementById('payloadModal').classList.remove('open')">&times;</span>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="form-row">
                    <label>Công cụ (Tool Name)</label>
                    <input type="text" name="tool_name" required placeholder="VD: nmap, msfvenom, bash...">
                </div>
                <div class="form-row">
                    <label>Hệ điều hành đích</label>
                    <select name="os_target">
                        <option value="Cross-platform">Cross-platform (Đa nền tảng)</option>
                        <option value="Linux">Linux</option>
                        <option value="Windows">Windows</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <label>Cú pháp Lệnh (Command Syntax)</label>
                <textarea name="command_syntax" required rows="3" placeholder="Ví dụ: nmap -sC -sV <IP>" style="font-family: monospace;"></textarea>
            </div>
            <div class="form-row">
                <label>Mô tả / Ghi chú</label>
                <input type="text" name="description" placeholder="Ý nghĩa của câu lệnh này...">
            </div>
            <button type="submit" class="btn btn-accent" style="width:100%; background: var(--blue); color: #fff;">Lưu Payload</button>
        </form>
    </div>
</div>

<script>
// Tự động căn chỉnh chiều cao cho textarea chứa code
document.querySelectorAll('textarea.p-cmd').forEach(el => {
    el.style.height = el.scrollHeight + 2 + 'px';
});

// Hàm Copy Command
function copyCommand(elementId) {
    const text = document.getElementById(elementId).value;
    navigator.clipboard.writeText(text).then(() => {
        alert("Đã sao chép câu lệnh!");
    });
}

// Bảng chuyển đổi Tiếng Việt có dấu (Unicode) <-> ký hiệu số kiểu gõ VNI
const VIET_TO_VNI_BASE = {
    'â':'a6','ă':'a8','ê':'e6','ô':'o6','ơ':'o7','ư':'u7','đ':'d9',
    'á':'a1','à':'a2','ả':'a3','ã':'a4','ạ':'a5',
    'ấ':'a61','ầ':'a62','ẩ':'a63','ẫ':'a64','ậ':'a65',
    'ắ':'a81','ằ':'a82','ẳ':'a83','ẵ':'a84','ặ':'a85',
    'é':'e1','è':'e2','ẻ':'e3','ẽ':'e4','ẹ':'e5',
    'ế':'e61','ề':'e62','ể':'e63','ễ':'e64','ệ':'e65',
    'í':'i1','ì':'i2','ỉ':'i3','ĩ':'i4','ị':'i5',
    'ó':'o1','ò':'o2','ỏ':'o3','õ':'o4','ọ':'o5',
    'ố':'o61','ồ':'o62','ổ':'o63','ỗ':'o64','ộ':'o65',
    'ớ':'o71','ờ':'o72','ở':'o73','ỡ':'o74','ợ':'o75',
    'ú':'u1','ù':'u2','ủ':'u3','ũ':'u4','ụ':'u5',
    'ứ':'u71','ừ':'u72','ử':'u73','ữ':'u74','ự':'u75',
    'ý':'y1','ỳ':'y2','ỷ':'y3','ỹ':'y4','ỵ':'y5'
};
const VIET_TO_VNI = {};
const VNI_TO_VIET = {};
for (const [ch, code] of Object.entries(VIET_TO_VNI_BASE)) {
    VIET_TO_VNI[ch] = code;
    VNI_TO_VIET[code] = ch;
    const chUp = ch.toUpperCase();
    const codeUp = code[0].toUpperCase() + code.slice(1);
    VIET_TO_VNI[chUp] = codeUp;
    VNI_TO_VIET[codeUp] = chUp;
}

// Tiếng Việt Unicode -> chuỗi VNI
function vietToVni(str) {
    return str.split('').map(c => VIET_TO_VNI[c] || c).join('');
}

// Chuỗi VNI -> Tiếng Việt Unicode
function vniToViet(str) {
    let out = '';
    let i = 0;
    while (i < str.length) {
        const c = str[i];
        if (/[aeiouydAEIOUYD]/.test(c)) {
            let matched = false;
            for (let len = 2; len >= 1; len--) {
                const chunk = c + str.substr(i + 1, len);
                if (VNI_TO_VIET[chunk] !== undefined) {
                    out += VNI_TO_VIET[chunk];
                    i += 1 + len;
                    matched = true;
                    break;
                }
            }
            if (!matched) { out += c; i += 1; }
        } else {
            out += c; i += 1;
        }
    }
    return out;
}

// Caesar +3
function caesar3Forward(str) {
    return str.split('').map(c => {
        if (c === 'I' || c === 'i') return '#';
        if (/[A-Z]/.test(c)) return String.fromCharCode((c.charCodeAt(0) - 65 + 3) % 26 + 65);
        if (/[a-z]/.test(c)) return String.fromCharCode((c.charCodeAt(0) - 97 + 3) % 26 + 97);
        if (/[0-9]/.test(c)) return String((parseInt(c, 10) + 3) % 10);
        return c;
    }).join('');
}
function caesar3Backward(str) {
    return str.split('').map(c => {
        if (c === '#') return 'I';
        if (/[A-Z]/.test(c)) return String.fromCharCode((c.charCodeAt(0) - 65 + 26 - 3) % 26 + 65);
        if (/[a-z]/.test(c)) return String.fromCharCode((c.charCodeAt(0) - 97 + 26 - 3) % 26 + 97);
        if (/[0-9]/.test(c)) return String((parseInt(c, 10) + 10 - 3) % 10);
        return c;
    }).join('');
}

// ĐÃ THÊM KHAI BÁO HÀM Ở ĐÂY
function processConversion() {
    const input = document.getElementById('enc_input').value;
    const mode = document.getElementById('enc_mode').value;
    const outputField = document.getElementById('enc_output');
    
    if (!input) {
        outputField.value = '';
        return;
    }
    
    try {
        let result = '';
        switch(mode) {
            case 'b64enc': result = btoa(unescape(encodeURIComponent(input))); break;
            case 'b64dec': result = decodeURIComponent(escape(atob(input))); break;
            case 'urlenc': result = encodeURIComponent(input); break;
            case 'urldec': result = decodeURIComponent(input); break;
            case 'hexenc': 
                result = input.split('').map(c => c.charCodeAt(0).toString(16).padStart(2, '0')).join(''); 
                break;
            case 'hexdec': 
                result = input.match(/.{1,2}/g).map(byte => String.fromCharCode(parseInt(byte, 16))).join(''); 
                break;
            case 'vnienc':
                result = caesar3Forward(vietToVni(input));
                break;
            case 'vnidec':
                result = vniToViet(caesar3Backward(input));
                break;
        }
        outputField.value = result;
    } catch (e) {
        outputField.value = "Lỗi: Đầu vào không hợp lệ cho phương thức giải mã này.";
    }
} // KẾT THÚC HÀM XỬ LÝ

// Chức năng sao chép kết quả Encode/Decode
function copyOutput() {
    const text = document.getElementById('enc_output').value;
    if(text && text !== "Lỗi: Đầu vào không hợp lệ cho phương thức giải mã này.") {
        navigator.clipboard.writeText(text).then(() => {
            alert("Đã sao chép kết quả!");
        });
    }
}

// Tính năng tìm kiếm động
function filterPayloads() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    const items = document.querySelectorAll('.payload-item');
    items.forEach(item => {
        const tool = item.querySelector('.p-tool').innerText.toLowerCase();
        const desc = item.querySelector('.p-desc').innerText.toLowerCase();
        const cmd = item.querySelector('.p-cmd').value.toLowerCase();
        
        if (tool.includes(query) || desc.includes(query) || cmd.includes(query)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>