/* theme.js — đặt trong <head> (trước nội dung) để không bị nháy sáng/tối. */
(function () {
    var KEY = 'qlht-theme';
    var DEFAULT = 'dark';           // 'dark' | 'light' | 'auto' (theo hệ điều hành)
    var root = document.documentElement;

    function stored() { try { return localStorage.getItem(KEY); } catch (e) { return null; } }
    function save(t)  { try { localStorage.setItem(KEY, t); } catch (e) {} }
    function initial() {
        var s = stored();
        if (s === 'light' || s === 'dark') return s;
        if (DEFAULT === 'auto') return window.matchMedia && matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
        return DEFAULT;
    }

    var SUN  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';
    var MOON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>';

    function apply(t) {
        root.setAttribute('data-theme', t);
        var b = document.getElementById('themeToggle');
        if (b) {
            b.innerHTML = t === 'dark' ? SUN : MOON;      // hiện biểu tượng của chế độ sẽ chuyển sang
            b.title = t === 'dark' ? 'Chuyển sang giao diện sáng' : 'Chuyển sang giao diện tối';
            b.setAttribute('aria-label', b.title);
        }
        document.dispatchEvent(new CustomEvent('themechange', {detail: t}));
    }

    apply(initial());

    document.addEventListener('DOMContentLoaded', function () {
        var b = document.getElementById('themeToggle');
        if (!b) {                                          // trang không dùng header.php (vd. login) => nút nổi
            b = document.createElement('button');
            b.id = 'themeToggle';
            b.type = 'button';
            b.className = 'floating';
            document.body.appendChild(b);
        }
        b.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            save(next);
            apply(next);
        });
        apply(root.getAttribute('data-theme'));
    });
})();