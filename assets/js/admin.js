/* ============================================================
   AutoPulse admin — tabs, toasts, confirms, media picker,
   editor toolbar, FAQ rows, slug helper, section ordering
   ============================================================ */
(() => {
    'use strict';
    const $  = (s, c = document) => c.querySelector(s);
    const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

    /* ── Toasts + flash messages ─────────────────────────────── */
    function toast(message, type = 'info', timeout = 4000) {
        const region = $('#toast-region');
        if (!region || !message) return;
        const el = document.createElement('div');
        el.className = `toast toast-${type}`;
        el.setAttribute('role', 'status');
        el.textContent = message;
        region.appendChild(el);
        setTimeout(() => { el.classList.add('leaving'); el.addEventListener('animationend', () => el.remove(), { once: true }); }, timeout);
    }
    const flash = $('#flash-data');
    if (flash) { try { (JSON.parse(flash.dataset.flash || '[]')).forEach(f => toast(f.message, f.type)); } catch (_) {} }

    /* ── Sidebar (mobile) ────────────────────────────────────── */
    const sidebar = $('#admin-sidebar'), backdrop = $('#admin-backdrop');
    $('#admin-burger')?.addEventListener('click', () => { sidebar.classList.add('open'); backdrop.hidden = false; requestAnimationFrame(() => backdrop.classList.add('show')); });
    backdrop?.addEventListener('click', () => { sidebar.classList.remove('open'); backdrop.classList.remove('show'); setTimeout(() => backdrop.hidden = true, 250); });

    /* ── Confirm dialogs ─────────────────────────────────────── */
    $$('form').forEach(form => form.addEventListener('submit', (e) => {
        const btn = e.submitter;
        if (btn?.dataset.confirm && !confirm(btn.dataset.confirm)) e.preventDefault();
    }));

    /* ── Tabs (settings page, car editor) ────────────────────── */
    $$('.tabs .tab[data-tab]').forEach(tab => tab.addEventListener('click', () => {
        const scope = tab.closest('.admin-content') || document;
        $$('.tabs .tab[data-tab]', scope).forEach(t => t.classList.toggle('active', t === tab));
        $$('.tab-panel', scope).forEach(p => { p.hidden = p.dataset.panel !== tab.dataset.tab; });
        const group = tab.closest('.tabs');
        if (group?.classList.contains('sticky-tabs')) {
            // keep only panels within the same form visible
            const form = group.closest('form') || scope;
            $$('.tab-panel', form).forEach(p => { p.hidden = p.dataset.panel !== tab.dataset.tab; });
        }
    }));

    /* ── Auto slug from title ────────────────────────────────── */
    $$('[data-slug-source]').forEach(src => {
        const target = $(src.dataset.slugSource);
        if (!target) return;
        src.addEventListener('input', () => {
            if (target.dataset.touched === '1') return;
            target.value = src.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            const prev = $('.slug-preview');
            if (prev) prev.textContent = target.value || 'your-slug';
        });
        target.addEventListener('input', () => {
            target.dataset.touched = target.value ? '1' : '0';
            const prev = $('.slug-preview');
            if (prev) prev.textContent = target.value || 'your-slug';
        });
    });

    /* ── FAQ repeater ────────────────────────────────────────── */
    $$('.faq-add').forEach(btn => btn.addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'faq-row';
        row.innerHTML = `
            <input type="text" name="faq_q[]" class="input" placeholder="Question">
            <textarea name="faq_a[]" class="textarea" rows="2" placeholder="Answer"></textarea>
            <button type="button" class="btn btn-sm btn-ghost faq-remove">Remove</button>`;
        btn.closest('.panel')?.querySelector('.faq-rows')?.appendChild(row);
    }));
    document.addEventListener('click', (e) => {
        if (e.target.matches('.faq-remove')) e.target.closest('.faq-row')?.remove();
    });

    /* ── Media picker modal ──────────────────────────────────── */
    function openPicker(onPick) {
        const backdropEl = document.createElement('div');
        backdropEl.className = 'modal-backdrop';
        backdropEl.innerHTML = `
            <div class="modal">
                <div class="modal-head"><h3>Choose image</h3>
                    <button type="button" class="icon-btn" aria-label="Close picker">✕</button></div>
                <div class="modal-body">
                    <iframe src="${document.body.dataset.base || '/'}admin/media.php?picker=1" style="width:100%;height:62vh;border:0"></iframe>
                </div>
            </div>`;
        document.body.appendChild(backdropEl);
        const close = () => { window.removeEventListener('message', onMessage); backdropEl.remove(); };
        function onMessage(ev) {
            if (ev.data?.type !== 'media-pick') return;
            onPick(ev.data.path, ev.data.url);
            close();
        }
        window.addEventListener('message', onMessage);
        backdropEl.addEventListener('click', (e) => { if (e.target === backdropEl) close(); });
        $('.icon-btn', backdropEl).addEventListener('click', close);
    }

    $$('.image-picker').forEach(picker => {
        const input = $('input[type="hidden"]', picker);
        const preview = $('.picker-preview', picker);
        $('.picker-open', picker)?.addEventListener('click', () => {
            openPicker((path, url) => {
                input.value = path;
                preview.src = url;
            });
        });
        $('.picker-clear', picker)?.addEventListener('click', () => {
            input.value = '';
            preview.src = preview.src; // falls back via PHP next save
            preview.style.opacity = 0.3;
        });
    });
    $$('.gallery-picker-open').forEach(btn => btn.addEventListener('click', () => {
        const target = document.getElementById(btn.dataset.target);
        openPicker((path, url) => {
            if (!target.value.includes(path)) {
                target.value = (target.value ? target.value.trimEnd() + '\n' : '') + path;
                toast('Image added to gallery — save to apply.', 'success');
            }
        });
    }));

    /* ── Editor toolbar (article content textarea) ───────────── */
    $$('.editor-toolbar').forEach(bar => {
        const ta = document.getElementById(bar.dataset.target);
        if (!ta) return;
        bar.addEventListener('click', (e) => {
            const btn = e.target.closest('button');
            if (!btn || !ta) return;
            e.preventDefault();
            const wrap = (before, after = before, placeholder = '') => {
                const { selectionStart: s, selectionEnd: en, value } = ta;
                const sel = value.slice(s, en) || placeholder;
                ta.setRangeText(before + sel + after, s, en, 'select');
                ta.focus();
            };
            if (btn.dataset.wrap) wrap(`<${btn.dataset.wrap}>`, `</${btn.dataset.wrap}>`, 'text');
            else if (btn.dataset.block) {
                const t = btn.dataset.block;
                wrap(`<${t}>`, `</${t}>`, t === 'p' ? 'Paragraph' : t === 'blockquote' ? 'Quote' : 'Heading');
            } else if (btn.dataset.list) wrap('<ul>\n  <li>', '</li>\n</ul>', 'Item');
            else if (btn.dataset.link) {
                const href = prompt('Link URL:', 'https://');
                if (href) wrap(`<a href="${href.replace(/"/g, '&quot;')}">`, '</a>', 'link text');
            } else if (btn.dataset.media) {
                openPicker((path) => {
                    const { selectionStart: s, selectionEnd: en } = ta;
                    ta.setRangeText(`\n<figure><img src="/${path}" alt="" loading="lazy"></figure>\n`, s, en, 'end');
                    toast('Image inserted.', 'success');
                });
            }
        });
    });

    /* ── Homepage section ordering ───────────────────────────── */
    $$('.section-move').forEach(btn => btn.addEventListener('click', () => {
        const row = btn.closest('tr');
        const rows = Array.from(row.parentNode.children).filter(r => r.tagName === 'TR');
        const i = rows.indexOf(row);
        const j = i + parseInt(btn.dataset.dir, 10);
        if (j < 0 || j >= rows.length) return;
        row.parentNode.insertBefore(row, btn.dataset.dir === '-1' ? rows[j] : rows[j].nextSibling);
    }));

    /* ── Media page: upload + delete + copy ──────────────────── */
    const uploadForm = $('#media-upload');
    if (uploadForm) {
        uploadForm.addEventListener('change', async () => {
            const input = $('#media-files');
            const status = $('#upload-status');
            if (!input.files.length) return;
            status.textContent = 'Uploading…';
            try {
                const fd = new FormData(uploadForm);
                const res = await fetch(location.pathname + location.search, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const json = await res.json();
                if (json.success && json.data?.length) {
                    status.textContent = `Uploaded ${json.data.length} image(s) ✓`;
                    setTimeout(() => location.reload(), 700);
                } else {
                    status.textContent = json.message || 'Upload failed.';
                }
            } catch { status.textContent = 'Upload failed — check file size and format.'; }
        });
    }
    document.addEventListener('click', async (e) => {
        const del = e.target.closest('.media-delete');
        if (del) {
            if (!confirm('Delete this image from the library?')) return;
            try {
                const fd = new FormData();
                fd.append('csrf_token', $('input[name="csrf_token"]')?.value || '');
                fd.append('action', 'delete');
                fd.append('id', del.dataset.id);
                const res = await fetch(location.pathname, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const json = await res.json();
                if (json.success) { del.closest('.media-item')?.remove(); toast('Image deleted.', 'success'); }
                else toast(json.message || 'Delete failed.', 'error');
            } catch { toast('Network error.', 'error'); }
        }
        const copy = e.target.closest('.copy-path');
        if (copy) {
            try { await navigator.clipboard.writeText(location.origin + copy.dataset.path); toast('URL copied.', 'success'); }
            catch { toast('Copy failed.', 'error'); }
        }
        const view = e.target.closest('.view-img');
        if (view) {
            const lb = document.createElement('div');
            lb.className = 'modal-backdrop';
            lb.innerHTML = `<div class="modal"><div class="modal-head"><h3>Preview</h3><button type="button" class="icon-btn">✕</button></div><div class="modal-body" style="padding:1rem"><img src="${view.dataset.src}" alt="" style="width:100%"></div></div>`;
            document.body.appendChild(lb);
            const close = () => lb.remove();
            lb.addEventListener('click', (ev) => { if (ev.target === lb) close(); });
            $('.icon-btn', lb).addEventListener('click', close);
        }
    });
})();
