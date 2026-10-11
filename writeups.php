<?php
require_once __DIR__ . '/config/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ghi chép bài học – QLHT</title>
<style>
:root{--bg:#0f1523;--card:#1a2236;--line:#2a3550;--txt:#e6ebf5;--mut:#8b97b3;--g:#88cc14}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--txt);font:15px 'Be Vietnam Pro',Arial,sans-serif}
.wrap{display:grid;grid-template-columns:320px 1fr;gap:16px;padding:16px;min-height:100vh}
@media(max-width:800px){.wrap{grid-template-columns:1fr}}
.panel{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:14px}
.aside-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.aside-head h2{margin:0;font-size:17px;color:var(--g)}
.back-link{color:var(--mut);text-decoration:none;font-size:13px;display:flex;align-items:center;gap:4px;transition:color .2s}
.back-link:hover{color:var(--g)}
h2{margin:0 0 10px;font-size:17px;color:var(--g)}
input,select,button{background:#0f1523;color:var(--txt);border:1px solid var(--line);border-radius:6px;padding:7px 9px;font:inherit}
button{cursor:pointer}button:hover{border-color:var(--g)}
.btn-g{background:var(--g);color:#0b1200;border-color:var(--g);font-weight:600}
.btn-r{color:#ff6b6b}
.filters{display:grid;gap:6px;margin-bottom:10px}.filters .row{display:flex;gap:6px}.filters .row>*{flex:1;min-width:0}
.grp{margin:12px 0 4px;color:var(--g);font-weight:600;font-size:13px;text-transform:uppercase}
.item{padding:7px 9px;border-radius:6px;cursor:pointer;display:flex;justify-content:space-between;gap:8px}
.item:hover,.item.on{background:#232e48}.item small{color:var(--mut);white-space:nowrap}
.meta{display:grid;grid-template-columns:1fr 170px;gap:8px;margin-bottom:8px}.meta input,.meta select{width:100%}
.tb{display:flex;flex-wrap:wrap;gap:4px;margin:8px 0;padding:6px;background:#0f1523;border:1px solid var(--line);border-radius:8px}
.tb button{padding:5px 9px;min-width:34px}
#editor{min-height:340px;padding:14px;background:#0f1523;border:1px solid var(--line);border-radius:8px;outline:none;line-height:1.6;overflow:auto}
#editor:focus{border-color:var(--g)}
#editor pre{background:#05080f;border:1px solid var(--line);border-left:3px solid var(--g);padding:10px 12px;border-radius:6px;overflow:auto;white-space:pre;tab-size:2}
#editor code{font-family:Consolas,'Courier New',monospace;color:#9be564}
#editor :not(pre)>code{background:#05080f;padding:1px 5px;border-radius:4px}
#editor blockquote{margin:8px 0;padding:4px 12px;border-left:3px solid var(--mut);color:var(--mut)}
#editor a{color:#5cc8ff}
.lk{display:grid;grid-template-columns:1fr 2fr auto auto;gap:6px;margin-bottom:6px}
.lk a{align-self:center;color:#5cc8ff;text-decoration:none}
.bar{display:flex;gap:8px;margin-top:12px;align-items:center}#msg{color:var(--mut);font-weight:500}
</style>
</head>
<body>
<div class="wrap">
  <aside class="panel">
    <div class="aside-head">
      <h2>📝 Ghi chép</h2>
      <a href="index.php" class="back-link">← Về trang chủ</a>
    </div>
    <button class="btn-g" id="new" style="width:100%;margin-bottom:10px">+ Ghi chép mới</button>
    <div class="filters">
      <input id="q" placeholder="Tìm tiêu đề / nội dung…">
      <select id="fSub"><option value="">Tất cả môn</option></select>
      <div class="row"><input type="date" id="fFrom" title="Từ ngày"><input type="date" id="fTo" title="Đến ngày"></div>
    </div>
    <div id="list"></div>
  </aside>

  <main class="panel">
    <div class="meta">
      <input id="title" placeholder="Tiêu đề bài học">
      <input type="date" id="date">
    </div>
    <div class="meta" style="grid-template-columns:1fr 1fr 110px">
      <select id="sub"></select>
      <select id="ffam"></select>
      <select id="fsz"></select>
    </div>

    <div class="tb" id="tb">
      <button data-c="bold"><b>B</b></button><button data-c="italic"><i>I</i></button><button data-c="underline"><u>U</u></button><button data-c="strikeThrough"><s>S</s></button>
      <button data-c="formatBlock" data-v="h2">H1</button><button data-c="formatBlock" data-v="h3">H2</button><button data-c="formatBlock" data-v="p">¶</button>
      <button data-c="insertUnorderedList">• List</button><button data-c="insertOrderedList">1. List</button>
      <button data-c="formatBlock" data-v="blockquote">❝</button>
      <button id="bCode" title="Khối code">&lt;/&gt; Code</button><button id="bInline" title="Code trong dòng">`code`</button>
      <select id="selFam" title="Font cho đoạn chọn"></select>
      <select id="selSz" title="Cỡ chữ cho đoạn chọn"></select>
      <button id="bLink">🔗 Link</button>
    </div>

    <div id="editor" contenteditable="true" spellcheck="false"></div>

    <h2 style="margin-top:16px">Tài liệu liên quan</h2>
    <div id="links"></div>
    <button id="addLink">+ Thêm link</button>

    <div class="bar">
      <button class="btn-g" id="save">Lưu</button>
      <button class="btn-r" id="del">Xoá</button>
      <span id="msg"></span>
    </div>
  </main>
</div>

<script>
const API = 'api/writeup_api.php';
const FONTS = ['Be Vietnam Pro','Arial','Times New Roman','Georgia','Consolas','Courier New','Comic Sans MS'];
const SIZES = [12,14,16,18,20,24,28,32];
const $ = s => document.querySelector(s), ed = $('#editor');
let curId = 0, subjects = [];

const api = async (a, params = {}, body) => {
  try {
    const res = await fetch(`${API}?action=${a}&` + new URLSearchParams(params),
      body ? {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:a, ...body})} : {});
    const data = await res.json();
    return data;
  } catch (err) {
    console.error('API Error:', err);
    return { error: 'Không thể kết nối tới máy chủ' };
  }
};

const esc = s => (s ? String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])) : '');
const say = t => { $('#msg').textContent = t; setTimeout(() => { if ($('#msg').textContent === t) $('#msg').textContent = ''; }, 3500); };

function fillSelect(el, items, first) {
  el.innerHTML = (first ? `<option value="">${first}</option>` : '') + items.map(i => `<option value="${i[0]}">${esc(String(i[1]))}</option>`).join('');
}
function applyDefaults() { ed.style.fontFamily = `'${$('#ffam').value}'`; ed.style.fontSize = $('#fsz').value + 'px'; }

/* ---------- danh sách ---------- */
async function loadList() {
  const rows = await api('list', {subject_id:$('#fSub').value, from:$('#fFrom').value, to:$('#fTo').value, q:$('#q').value});
  let html = '', last = null;
  if (Array.isArray(rows)) {
    for (const r of rows) {
      if (r.subject_id !== last) { html += `<div class="grp">${esc(r.subject_name || '')}</div>`; last = r.subject_id; }
      const d = r.study_date ? r.study_date.split('-').reverse().join('/') : '';
      html += `<div class="item ${r.id == curId ? 'on' : ''}" data-id="${r.id}"><span>${esc(r.title || '')}</span><small>${d}</small></div>`;
    }
  }
  $('#list').innerHTML = html || '<p style="color:var(--mut)">Chưa có ghi chép.</p>';
}
$('#list').onclick = e => { const it = e.target.closest('.item'); if (it) open(+it.dataset.id); };
['#q','#fSub','#fFrom','#fTo'].forEach(s => $(s).addEventListener('input', loadList));

/* ---------- mở / tạo ---------- */
function resetForm() {
  curId = 0; $('#title').value = ''; ed.innerHTML = '<p><br></p>'; $('#links').innerHTML = '';
  $('#date').value = new Date().toISOString().slice(0, 10);
  $('#ffam').value = FONTS[0]; $('#fsz').value = 16; applyDefaults();
}
async function open(id) {
  const w = await api('get', {id});
  if (w.error) return say(w.error);
  curId = w.id; $('#title').value = w.title || ''; $('#date').value = w.study_date || '';
  if (w.subject_id) $('#sub').value = w.subject_id;
  $('#ffam').value = w.font_family || FONTS[0]; $('#fsz').value = w.font_size || 16; applyDefaults();
  ed.innerHTML = w.content || '<p><br></p>';
  $('#links').innerHTML = '';
  if (Array.isArray(w.links)) {
    w.links.forEach(l => addLink(l.title, l.url));
  }
  loadList();
}
$('#new').onclick = () => { resetForm(); loadList(); };
['#ffam','#fsz'].forEach(s => $(s).addEventListener('change', applyDefaults));

/* ---------- links tài liệu ---------- */
function addLink(t = '', u = '') {
  const d = document.createElement('div'); d.className = 'lk';
  d.innerHTML = `<input placeholder="Tên tài liệu" value="${esc(t)}"><input placeholder="https://…" value="${esc(u)}"><a target="_blank" rel="noopener">Mở ↗</a><button>✕</button>`;
  const [ti, ui] = d.querySelectorAll('input'), a = d.querySelector('a');
  const sync = () => a.href = /^https?:\/\//i.test(ui.value) ? ui.value : '#'; sync(); ui.oninput = sync;
  d.querySelector('button').onclick = () => d.remove();
  $('#links').appendChild(d);
}
$('#addLink').onclick = () => addLink();

/* ---------- editor ---------- */
$('#tb').addEventListener('mousedown', e => { if (e.target.closest('button')) e.preventDefault(); });
$('#tb').addEventListener('click', e => {
  const b = e.target.closest('button[data-c]'); if (!b) return;
  document.execCommand(b.dataset.c, false, b.dataset.v || null);
});
$('#bCode').onclick = () => {
  const t = getSelection().toString();
  document.execCommand('insertHTML', false, `<pre><code>${esc(t) || '\n'}</code></pre><p><br></p>`);
};
$('#bInline').onclick = () => {
  const t = getSelection().toString(); if (t) document.execCommand('insertHTML', false, `<code>${esc(t)}</code>&nbsp;`);
};
$('#bLink').onclick = () => {
  const u = prompt('Nhập URL (https://…)'); if (u && /^https?:\/\//i.test(u)) document.execCommand('createLink', false, u);
};
function styleSel(kind, val) {
  if (!val) return;
  document.execCommand(kind === 'size' ? 'fontSize' : 'fontName', false, kind === 'size' ? '7' : val);
  ed.querySelectorAll('font').forEach(f => {
    const s = document.createElement('span');
    s.style[kind === 'size' ? 'fontSize' : 'fontFamily'] = kind === 'size' ? val + 'px' : `'${val}'`;
    while (f.firstChild) s.appendChild(f.firstChild);
    f.replaceWith(s);
  });
}
$('#selFam').onchange = e => { styleSel('fam', e.target.value); e.target.value = ''; };
$('#selSz').onchange = e => { styleSel('size', e.target.value); e.target.value = ''; };
ed.addEventListener('paste', e => { e.preventDefault(); document.execCommand('insertText', false, e.clipboardData.getData('text/plain')); });
ed.addEventListener('keydown', e => {
  if (e.key === 'Tab') { e.preventDefault(); document.execCommand('insertText', false, '  '); }
});

/* ---------- lưu / xoá ---------- */
$('#save').onclick = async () => {
  const title = $('#title').value.trim();
  const subId = $('#sub').value;
  if (!subId) return say('⚠ Vui lòng chọn môn học');
  if (!title) return say('⚠ Vui lòng nhập tiêu đề bài học');

  const links = [...document.querySelectorAll('#links .lk')].map(d => {
    const i = d.querySelectorAll('input');
    return {title: i[0].value.trim(), url: i[1].value.trim()};
  }).filter(l => l.url !== '' || l.title !== '');

  const r = await api('save', {}, {
    id: curId,
    subject_id: subId,
    study_date: $('#date').value,
    title: title,
    content: ed.innerHTML,
    font_family: $('#ffam').value,
    font_size: $('#fsz').value,
    links: links
  });
  if (r.error) return say('⚠ ' + r.error);
  curId = r.id;
  say('Đã lưu thành công ✓');
  loadList();
};

$('#del').onclick = async () => {
  if (!curId || !confirm('Xoá ghi chép này?')) return;
  const r = await api('delete', {}, {id: curId});
  if (r.error) return say('⚠ ' + r.error);
  resetForm();
  loadList();
};

/* ---------- khởi động ---------- */
(async () => {
  subjects = await api('subjects');
  if (Array.isArray(subjects)) {
    const opts = subjects.map(s => [s.id, s.name || s.subject_name]);
    fillSelect($('#sub'), opts);
    fillSelect($('#fSub'), opts, 'Tất cả môn');
  } else if (subjects && subjects.error) {
    say('⚠ ' + subjects.error);
  }
  fillSelect($('#ffam'), FONTS.map(f => [f, f]));
  fillSelect($('#fsz'), SIZES.map(s => [s, s + ' px']));
  fillSelect($('#selFam'), FONTS.map(f => [f, f]), 'Font…');
  fillSelect($('#selSz'), SIZES.map(s => [s, s + ' px']), 'Cỡ…');
  resetForm();
  loadList();
})();
</script>
</body>
</html>