/* ============================================================
   AutoPulse — front-end JavaScript (vanilla, no dependencies)
   Sections: helpers · toasts · nav · search · gallery · reveal ·
             share · ajax forms · compare
   ============================================================ */
(() => {
    'use strict';

    /* ── Helpers ─────────────────────────────────────────────── */
    const $  = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
    const debounce = (fn, ms) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
    const prefersReducedMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

    async function fetchJSON(url, options = {}) {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, ...options });
        let data = null;
        try { data = await res.json(); } catch (_) { /* non-JSON error */ }
        if (!res.ok || !data) throw new Error(data?.message || 'Network error — please try again.');
        return data;
    }

    /* ── Toasts ──────────────────────────────────────────────── */
    function toast(message, type = 'info', timeout = 4200) {
        const region = $('#toast-region');
        if (!region) return;
        const el = document.createElement('div');
        el.className = `toast toast-${type}`;
        el.setAttribute('role', 'status');
        el.textContent = message;
        region.appendChild(el);
        setTimeout(() => {
            el.classList.add('leaving');
            el.addEventListener('animationend', () => el.remove(), { once: true });
        }, timeout);
    }
    window.AutoPulseToast = toast;

    // Flash messages rendered by PHP
    const flashData = $('#flash-data');
    if (flashData) {
        try { (JSON.parse(flashData.dataset.flash || '[]')).forEach(f => toast(f.message, f.type)); } catch (_) {}
    }

    /* ── Theme toggle ────────────────────────────────────────── */
    $$('.theme-toggle').forEach(btn => btn.addEventListener('click', () => {
        const html = document.documentElement;
        const next = html.dataset.theme === 'dark' ? 'light' : 'dark';
        html.dataset.theme = next;
        localStorage.setItem('theme', next);
    }));

    /* ── Header state on scroll ──────────────────────────────── */
    const header = $('#site-header');
    if (header) {
        const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 8);
        onScroll();
        addEventListener('scroll', onScroll, { passive: true });
    }

    /* ── Mobile drawer ───────────────────────────────────────── */
    const burger = $('#nav-burger'), drawer = $('#mobile-nav'), backdrop = $('#drawer-backdrop');
    function setDrawer(open) {
        if (!drawer) return;
        drawer.hidden = false;
        requestAnimationFrame(() => {
            drawer.classList.toggle('show', open);
            backdrop.classList.toggle('show', open);
            document.body.style.overflow = open ? 'hidden' : '';
        });
        burger?.setAttribute('aria-expanded', String(open));
        if (!open) setTimeout(() => { if (!drawer.classList.contains('show')) drawer.hidden = true; }, 350);
    }
    burger?.addEventListener('click', () => setDrawer(true));
    $('#nav-close')?.addEventListener('click', () => setDrawer(false));
    backdrop?.addEventListener('click', () => setDrawer(false));

    /* ── Desktop dropdowns (click for touch+keyboard, CSS hover for pointer) ── */
    $$('.nav-drop-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const li = btn.closest('li');
            const open = li.classList.toggle('open');
            btn.setAttribute('aria-expanded', String(open));
        });
    });
    document.addEventListener('click', () => $$('.has-dropdown.open').forEach(li => {
        li.classList.remove('open');
        li.querySelector('.nav-drop-btn')?.setAttribute('aria-expanded', 'false');
    }));

    /* ── Search overlay + live suggestions ───────────────────── */
    const overlay = $('#search-overlay'), input = $('#search-input'), suggest = $('#search-suggest');
    function openSearch() {
        if (!overlay) return;
        overlay.hidden = false;
        requestAnimationFrame(() => overlay.classList.add('show'));
        document.body.style.overflow = 'hidden';
        setTimeout(() => input?.focus(), 60);
    }
    function closeSearch() {
        if (!overlay) return;
        overlay.classList.remove('show');
        document.body.style.overflow = '';
        setTimeout(() => { if (!overlay.classList.contains('show')) overlay.hidden = true; }, 300);
    }
    $('#search-open')?.addEventListener('click', openSearch);
    $('#search-close')?.addEventListener('click', closeSearch);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { closeSearch(); setDrawer(false); }
        if (e.key === '/' && !/input|textarea|select/i.test(document.activeElement?.tagName || '')) { e.preventDefault(); openSearch(); }
    });
    overlay?.addEventListener('click', (e) => { if (e.target === overlay) closeSearch(); });

    const highlight = (text, q) => {
        const i = text.toLowerCase().indexOf(q.toLowerCase());
        return i < 0 ? text : text.slice(0, i) + '<mark>' + text.slice(i, i + q.length) + '</mark>' + text.slice(i + q.length);
    };

    if (input && suggest) {
        let controller = null, activeIndex = -1, items = [];
        const doSearch = debounce(async () => {
            const q = input.value.trim();
            activeIndex = -1;
            if (q.length < 2) { suggest.innerHTML = ''; suggest.style.display = 'none'; return; }
            suggest.style.display = 'block';
            suggest.innerHTML = '<div class="suggest-loading" aria-label="Searching"><span></span><span></span><span></span></div>';
            try {
                controller?.abort();
                controller = new AbortController();
                const json = await fetchJSON(`api/search.php?q=${encodeURIComponent(q)}`, { signal: controller.signal });
                const d = json.data || {};
                items = [...(d.cars || []), ...(d.brands || []), ...(d.articles || [])];
                if (!items.length) { suggest.innerHTML = '<div class="suggest-empty">No matches — press Enter for full search.</div>'; return; }
                const group = (label, arr) => arr.length ? `<div class="suggest-group"><div class="suggest-label">${label}</div>${
                    arr.map((it, i) => `<a class="suggest-item" href="${it.url}"><img src="${it.image}" alt="" loading="lazy" onerror="this.style.visibility='hidden'"><span class="suggest-title">${highlight(it.title, q)}</span><span class="suggest-sub">${it.sub}</span></a>`).join('')
                }</div>` : '';
                suggest.innerHTML = group('Cars', d.cars || []) + group('Brands', d.brands || []) + group('Articles', d.articles || [])
                    + `<a class="suggest-item" href="search?q=${encodeURIComponent(q)}"><span class="suggest-title">View all results for “${q}”</span></a>`;
            } catch (err) {
                if (err.name !== 'AbortError') suggest.innerHTML = '<div class="suggest-empty">Search is unavailable right now.</div>';
            }
        }, 280);
        input.addEventListener('input', doSearch);
        input.addEventListener('keydown', (e) => {
            const links = $$('.suggest-item', suggest);
            if (!links.length) return;
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = e.key === 'ArrowDown' ? (activeIndex + 1) % links.length : (activeIndex - 1 + links.length) % links.length;
                links.forEach((l, i) => l.classList.toggle('active', i === activeIndex));
                links[activeIndex].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter' && activeIndex >= 0) {
                e.preventDefault();
                links[activeIndex].click();
            }
        });
    }

    /* ── Image gallery + lightbox ────────────────────────────── */
    const gallery = $('[data-gallery]');
    if (gallery) {
        const main = $('#gallery-main-img');
        $$('.gallery-thumb', gallery).forEach(thumb => thumb.addEventListener('click', () => {
            if (main) {
                main.src = thumb.dataset.full;
                main.alt = thumb.dataset.alt || '';
            }
            $$('.gallery-thumb', gallery).forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
        }));
        const mainWrap = $('.gallery-main', gallery);
        mainWrap?.addEventListener('click', () => {
            const lightbox = document.createElement('div');
            lightbox.className = 'modal-backdrop';
            lightbox.innerHTML = `<div class="lightbox"><img src="${main.src}" alt="${main.alt}"><button type="button" class="icon-btn lightbox-close" aria-label="Close">✕</button></div>`;
            document.body.appendChild(lightbox);
            document.body.style.overflow = 'hidden';
            const close = () => { lightbox.remove(); document.body.style.overflow = ''; };
            lightbox.addEventListener('click', (e) => { if (e.target === lightbox) close(); });
            $('.lightbox-close', lightbox).addEventListener('click', close);
            document.addEventListener('keydown', function esc(ev) { if (ev.key === 'Escape') { close(); document.removeEventListener('keydown', esc); } });
        });
    }

    /* ── Reveal-on-scroll animation (respects reduced motion) ── */
    const revealEls = $$('.reveal');
    if (revealEls.length && !prefersReducedMotion && 'IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => entries.forEach(en => {
            if (en.isIntersecting) { en.target.classList.add('visible'); io.unobserve(en.target); }
        }), { threshold: 0.08 });
        revealEls.forEach(el => io.observe(el));
    } else {
        revealEls.forEach(el => el.classList.add('visible'));
    }

    /* ── Share buttons ───────────────────────────────────────── */
    $$('[data-share-url]').forEach(box => {
        const url = encodeURIComponent(box.dataset.shareUrl);
        const title = encodeURIComponent(box.dataset.shareTitle || document.title);
        $$('[data-share]', box).forEach(btn => btn.addEventListener('click', async () => {
            const kind = btn.dataset.share;
            const links = {
                facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
                twitter:  `https://twitter.com/intent/tweet?url=${url}&text=${title}`,
                whatsapp: `https://wa.me/?text=${title}%20${url}`,
            };
            if (kind === 'copy') {
                try { await navigator.clipboard.writeText(decodeURIComponent(url)); toast('Link copied to clipboard.', 'success'); }
                catch { toast('Could not copy the link.', 'error'); }
            } else if (links[kind]) {
                open(links[kind], '_blank', 'noopener,width=620,height=520');
            }
        }));
    });

    /* ── AJAX forms (contact · comment · newsletter) ─────────── */
    function handleAjaxForm(form, { inline = true, successFlash = true } = {}) {
        if (!form) return;
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fields = $$('input, textarea, select', form).filter(f => f.type !== 'hidden');
            let valid = true;

            // Inline validation
            fields.forEach(f => {
                const wrap = f.closest('.form-field');
                if (wrap) wrap.classList.remove('invalid');
                if (f.type === 'email' ? !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(f.value) : f.required && !f.checkValidity()) valid = false;
                if (f.required && !f.value) wrap?.classList.add('invalid');
                else if (f.type === 'email' && f.value && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(f.value)) { wrap?.classList.add('invalid'); valid = false; }
            });
            if (!valid) { toast('Please fix the highlighted fields.', 'error'); return; }

            const btn = form.querySelector('[type="submit"]');
            const original = btn?.textContent;
            if (btn) { btn.disabled = true; btn.textContent = 'Sending…'; }
            try {
                const data = new FormData(form);
                data.set('csrf_token', form.querySelector('[name="csrf_token"]')?.value || '');
                const json = await fetchJSON(form.action || location.pathname + '/api/' , { method: 'POST', body: data });
                if (json.success) {
                    form.reset();
                    if (successFlash) toast(json.message || 'Done.', 'success');
                } else {
                    toast(json.message || 'Submission failed.', 'error');
                    if (inline && json.errors) Object.entries(json.errors).forEach(([name]) => {
                        const f = form.querySelector(`[name="${name}"]`);
                        f?.closest('.form-field')?.classList.add('invalid');
                    });
                }
            } catch (err) {
                toast(err.message || 'Network error — please try again.', 'error');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = original; }
            }
        });
    }

    // Forms post to their page-relative API endpoints
    const apiBase = document.body.dataset.apiBase || '';
    const contactForm = $('#contact-form');
    if (contactForm) contactForm.action = 'api/contact.php', handleAjaxForm(contactForm);
    const commentForm = $('#comment-form');
    if (commentForm) commentForm.action = 'api/comments.php', handleAjaxForm(commentForm);
    const newsletterForm = $('#newsletter-form');
    if (newsletterForm) {
        newsletterForm.action = 'api/newsletter.php';
        handleAjaxForm(newsletterForm, { inline: false });
    }

    /* ── Compare picks (persisted locally) + compare page ────── */
    const CMP_KEY = 'autopulse_compare';
    const getPicks = () => { try { return JSON.parse(localStorage.getItem(CMP_KEY) || '[]').slice(0, 3); } catch { return []; } };
    const setPicks = (ids) => localStorage.setItem(CMP_KEY, JSON.stringify(ids.slice(0, 3)));

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.add-compare');
        if (!btn) return;
        e.preventDefault();
        let ids = getPicks();
        const id = parseInt(btn.dataset.id, 10);
        if (ids.includes(id)) { ids = ids.filter(x => x !== id); toast('Removed from comparison.', 'info', 2200); }
        else if (ids.length >= 3) { toast('You can compare up to 3 cars — remove one first.', 'error'); return; }
        else { ids.push(id); toast('Added to comparison.', 'success', 2200); }
        setPicks(ids);
        btn.textContent = ids.includes(id) ? '✓ Added' : '+ Compare';
        btn.setAttribute('aria-pressed', ids.includes(id) ? 'true' : 'false');
    });

    const compareStatus = $('#compare-status');
    const compareResults = $('#compare-results');
    if (compareResults && $('#compare-picker')) {
        const brandSel = $('#cmp-brand'), modelSel = $('#cmp-model'), addBtn = $('#cmp-add');
        const initial = ($('#compare-picker').dataset.initial || '').split(',').filter(Boolean).map(Number);
        let picks = [...new Set([...initial.filter(n => n > 0), ...getPicks()])].slice(0, 3);
        if (initial.length) setPicks(picks);

        brandSel?.addEventListener('change', async () => {
            modelSel.innerHTML = '<option value="">Loading…</option>';
            modelSel.disabled = true;
            addBtn.disabled = true;
            try {
                const json = await fetchJSON(`api/cars.php?brand_id=${brandSel.value}`);
                modelSel.innerHTML = '<option value="">Select model…</option>' +
                    json.data.map(c => `<option value="${c.id}">${c.label}</option>`).join('');
                modelSel.disabled = false;
            } catch {
                modelSel.innerHTML = '<option value="">Failed to load</option>';
            }
        });
        modelSel?.addEventListener('change', () => { addBtn.disabled = !modelSel.value; });
        addBtn?.addEventListener('click', () => {
            const id = parseInt(modelSel.value, 10);
            if (!id || picks.includes(id)) { toast('Already added.', 'info', 2000); return; }
            if (picks.length >= 3) { toast('Maximum 3 cars.', 'error'); return; }
            picks.push(id);
            setPicks(picks);
            render();
        });
        $('#cmp-clear')?.addEventListener('click', () => {
            picks = [];
            setPicks([]);
            render();
        });

        async function render() {
            if (!picks.length) {
                compareStatus.innerHTML = '';
                compareResults.innerHTML = `<div class="empty-state"><div class="icon">⚖️</div><h3>Select cars to compare</h3><p>Choose a brand and model above, or press “+ Compare” on any car card.</p></div>`;
                return;
            }
            compareStatus.innerHTML = `<div class="spinner" style="margin:0 auto 1.4rem" role="status" aria-label="Loading comparison"></div>`;
            try {
                const json = await fetchJSON(`api/compare.php?ids=${picks.join(',')}`);
                const cars = json.data;
                const labels = Object.keys(cars[0]?.specs || {});
                const row = (label) => {
                    const vals = cars.map(c => (c.specs[label] || '—').trim());
                    const differing = new Set(vals).size > 1;
                    return `<tr><th scope="row" class="spec-label">${label}</th>${cars.map((c, i) =>
                        `<td class="${differing ? 'diff' : ''}">${vals[i] === '' ? '—' : vals[i]}</td>`).join('')}</tr>`;
                };
                compareResults.innerHTML = `
                    <div class="compare-table-wrap">
                        <table class="compare-table">
                            <thead><tr>
                                <th class="spec-label" style="background:transparent"></th>
                                ${cars.map(c => `<th>
                                    <button type="button" class="icon-btn cmp-remove" data-id="${c.id}" aria-label="Remove ${c.name}" style="color:#fff;float:right">✕</button>
                                    <img src="${c.image}" alt="" loading="lazy">
                                    <a href="${c.url}" style="color:#fff">${c.name}</a><br>
                                    <small style="font-family:var(--font-body);font-weight:500;font-size:.72em">${c.price}</small>
                                </th>`).join('')}
                            </tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>`;
                // Build body: price first, then spec rows (differences highlighted), then safety lists
                const priceRow = `<tr><th scope="row" class="spec-label">Price</th>${cars.map(c => `<td><strong>${c.price}</strong></td>`).join('')}</tr>`;
                const safetyRow = cars.some(c => c.safety.length)
                    ? `<tr><th scope="row" class="spec-label">Safety features</th>${cars.map(c => `<td>${c.safety.length ? `<ul style="margin:0;padding-left:1.1em">${c.safety.slice(0, 6).map(s => `<li>${s}</li>`).join('')}</ul>` : '—'}</td>`).join('')}</tr>` : '';
                $('.compare-table tbody').innerHTML = priceRow + labels.map(row).join('') + safetyRow;
                $$('.cmp-remove', compareResults).forEach(btn => btn.addEventListener('click', () => {
                    picks = picks.filter(id => id !== parseInt(btn.dataset.id, 10));
                    setPicks(picks);
                    render();
                }));
            } catch (err) {
                compareStatus.innerHTML = '';
                compareResults.innerHTML = `<div class="empty-state"><div class="icon">⚠️</div><h3>Could not load comparison</h3><p>${err.message}</p></div>`;
            }
        }
        render();
    }
})();
