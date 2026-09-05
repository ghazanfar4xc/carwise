<?php
/** AutoPulse admin — shared SEO/AEO/GEO metabox renderer + persist helper.
 *  Used by article-edit.php and car-edit.php. Keeps editors DRY.
 */
if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

/** Render the SEO + AEO + GEO fields for a content entity. */
function render_seo_fields(string $entity, string $entityType, int $entityId, array $v): void
{
    $isArticle = $entityType === 'article';
    $sources   = $entityId ? get_sources($entityType, $entityId) : [];
    $answers   = $entityId ? get_answer_blocks($entityType, $entityId) : [];
    ?>
<section class="panel" id="seo-panel">
    <div class="panel-head"><h2>SEO, AEO &amp; GEO</h2></div>
    <div class="admin-form">
        <div class="form-row cols-2">
            <div class="form-field">
                <label for="seo-title">SEO title <span class="hint" style="display:inline">≈ 50–60 characters</span></label>
                <input type="text" id="seo-title" name="seo_title" class="input" maxlength="150" data-counter="seo-title-count" value="<?= e($v['seo_title'] ?? '') ?>">
                <p class="hint"><span id="seo-title-count"></span> Leave empty to use the default title.</p>
            </div>
            <div class="form-field">
                <label for="seo-focus">Focus topic / primary keyword</label>
                <input type="text" id="seo-focus" name="focus_keyword" class="input" maxlength="120" value="<?= e($v['focus_keyword'] ?? '') ?>" placeholder="e.g. 2026 Toyota Camry">
            </div>
        </div>
        <div class="form-field">
            <label for="seo-meta">Meta description <span class="hint" style="display:inline">≈ 140–160 characters</span></label>
            <textarea id="seo-meta" name="meta_description" class="textarea" rows="2" maxlength="300" data-counter="seo-meta-count"><?= e($v['meta_description'] ?? '') ?></textarea>
            <p class="hint"><span id="seo-meta-count"></span> Leave empty to auto-generate from the excerpt/overview.</p>
        </div>
        <div class="form-row cols-2">
            <div class="form-field">
                <label for="seo-canonical">Canonical URL <span class="hint" style="display:inline">(optional override)</span></label>
                <input type="text" id="seo-canonical" name="canonical_url" class="input" value="<?= e($v['canonical_url'] ?? '') ?>" placeholder="Auto-generated when empty">
            </div>
            <div class="form-field">
                <label for="seo-robots">Robots directive</label>
                <select id="seo-robots" name="robots" class="select">
                    <?php $cur = $v['robots'] ?? 'index, follow'; ?>
                    <?php foreach (['index, follow' => 'Index, follow (default)', 'noindex, follow' => 'Noindex, follow', 'noindex, nofollow' => 'Noindex, nofollow'] as $val => $lbl): ?>
                    <option value="<?= $val ?>" <?= $cur === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row cols-2">
            <div class="form-field">
                <label for="og-title">Open Graph title <span class="hint" style="display:inline">(social shares)</span></label>
                <input type="text" id="og-title" name="og_title" class="input" maxlength="150" value="<?= e($v['og_title'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label for="og-image">Open Graph image path <span class="hint" style="display:inline">(from Media)</span></label>
                <input type="text" id="og-image" name="og_image" class="input" value="<?= e($v['og_image'] ?? '') ?>" placeholder="uploads/…">
            </div>
        </div>
        <div class="form-field">
            <label for="og-desc">Open Graph description</label>
            <textarea id="og-desc" name="og_description" class="textarea" rows="2" maxlength="300"><?= e($v['og_description'] ?? '') ?></textarea>
        </div>
        <?php if ($isArticle): ?>
        <div class="form-row cols-2">
            <div class="form-field">
                <label for="seo-intent">Search intent</label>
                <select id="seo-intent" name="search_intent" class="select">
                    <?php $cur = $v['search_intent'] ?? ''; ?>
                    <?php foreach (['' => '— not set —', 'informational' => 'Informational', 'commercial' => 'Commercial (comparing)', 'transactional' => 'Transactional', 'navigational' => 'Navigational'] as $val => $lbl): ?>
                    <option value="<?= $val ?>" <?= $cur === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="seo-secondary">Secondary keywords <span class="hint" style="display:inline">(comma-separated, natural use only)</span></label>
                <input type="text" id="seo-secondary" name="secondary_keywords" class="input" maxlength="500" value="<?= e($v['secondary_keywords'] ?? '') ?>">
            </div>
        </div>
        <?php endif; ?>

        <div class="form-field">
            <label for="quick-answer">Quick Answer (AEO) <span class="hint" style="display:inline">— 1–3 factual sentences shown at the top of the page and fed to answer engines. Use only verified facts.</span></label>
            <textarea id="quick-answer" name="quick_answer" class="textarea" rows="3" placeholder="e.g. The 2026 Camry LE Hybrid is EPA-rated at 53 mpg city and starts at $28,995."><?= e($v['quick_answer'] ?? '') ?></textarea>
        </div>
        <div class="form-row cols-2">
            <div class="form-field">
                <label for="last-verified">Last verified date</label>
                <input type="date" id="last-verified" name="last_verified_at" class="input" value="<?= e($v['last_verified_at'] ? date('Y-m-d', strtotime($v['last_verified_at'])) : '') ?>">
                <p class="hint">Update only when facts were actually re-checked.</p>
            </div>
            <?php if ($isArticle): ?>
            <div class="form-field">
                <label for="fact-checked">Fact checked by <span class="hint" style="display:inline">(only if a real process exists)</span></label>
                <input type="text" id="fact-checked" name="fact_checked_by" class="input" maxlength="120" value="<?= e($v['fact_checked_by'] ?? '') ?>">
            </div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label>Sources (GEO) <span class="hint" style="display:inline">— attach the references this content is based on. Never invent sources.</span></label>
            <div id="sources-repeater" data-repeater>
                <?php foreach ($sources as $i => $src): ?>
                <div class="repeater-row" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:.5rem;align-items:end;margin-bottom:.5rem">
                    <input type="text" name="src_name[]" class="input" placeholder="Source name" value="<?= e($src['name']) ?>">
                    <input type="url" name="src_url[]" class="input" placeholder="https:// (optional)" value="<?= e($src['url'] ?? '') ?>">
                    <select name="src_type[]" class="select">
                        <?php foreach (['manufacturer' => 'Manufacturer', 'government' => 'Government', 'regulator' => 'Regulator', 'research' => 'Research', 'official_documentation' => 'Official docs', 'independent_testing' => 'Independent testing', 'other' => 'Other'] as $tk => $tl): ?>
                        <option value="<?= $tk ?>" <?= $src['source_type'] === $tk ? 'selected' : '' ?>><?= $tl ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-sm btn-outline" data-repeater-remove>✕</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-outline" data-repeater-add="#sources-repeater" data-tpl="src">+ Add source</button>
        </div>

        <div class="form-field">
            <label>Answer Blocks (AEO) <span class="hint" style="display:inline">— question → direct answer → explanation, shown as an accessible Q&amp;A section.</span></label>
            <div id="answers-repeater" data-repeater>
                <?php foreach ($answers as $ab): ?>
                <div class="repeater-row" style="margin-bottom:.5rem">
                    <input type="text" name="ab_question[]" class="input" placeholder="Question" value="<?= e($ab['question']) ?>" style="margin-bottom:.35rem">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
                        <textarea name="ab_short[]" class="textarea" rows="2" placeholder="Direct short answer (1–2 sentences)"><?= e($ab['short_answer']) ?></textarea>
                        <textarea name="ab_explain[]" class="textarea" rows="2" placeholder="Detailed explanation (optional)"><?= e($ab['explanation'] ?? '') ?></textarea>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline" style="margin-top:.35rem" data-repeater-remove>✕ Remove</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-outline" data-repeater-add="#answers-repeater" data-tpl="ab">+ Add answer block</button>
        </div>
    </div>

    <div class="panel-head" style="margin-top:1.5rem"><h2>Content Optimization Checklist</h2><p class="hint">Advisory only — not a ranking score.</p></div>
    <ul class="seo-checklist" id="seo-checklist" data-entity="<?= e($entityType) ?>"></ul>
</section>
<script>
(function () {
    // live character counters
    document.querySelectorAll('[data-counter]').forEach(function (el) {
        var out = document.getElementById(el.dataset.counter);
        var upd = function () { out.textContent = el.value.length + ' characters'; };
        el.addEventListener('input', upd); upd();
    });
    // repeaters
    var SRC_TPL = '<div class="repeater-row" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:.5rem;align-items:end;margin-bottom:.5rem">'
        + '<input type="text" name="src_name[]" class="input" placeholder="Source name">'
        + '<input type="url" name="src_url[]" class="input" placeholder="https:// (optional)">'
        + '<select name="src_type[]" class="select"><option value="manufacturer">Manufacturer</option><option value="government">Government</option><option value="regulator">Regulator</option><option value="research">Research</option><option value="official_documentation">Official docs</option><option value="independent_testing">Independent testing</option><option value="other">Other</option></select>'
        + '<button type="button" class="btn btn-sm btn-outline" data-repeater-remove>✕</button></div>';
    var AB_TPL = '<div class="repeater-row" style="margin-bottom:.5rem">'
        + '<input type="text" name="ab_question[]" class="input" placeholder="Question" style="margin-bottom:.35rem">'
        + '<div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">'
        + '<textarea name="ab_short[]" class="textarea" rows="2" placeholder="Direct short answer (1–2 sentences)"></textarea>'
        + '<textarea name="ab_explain[]" class="textarea" rows="2" placeholder="Detailed explanation (optional)"></textarea></div>'
        + '<button type="button" class="btn btn-sm btn-outline" style="margin-top:.35rem" data-repeater-remove>✕ Remove</button></div>';
    document.addEventListener('click', function (e) {
        var add = e.target.closest('[data-repeater-add]');
        if (add) {
            var wrap = document.querySelector(add.dataset.repeaterAdd);
            wrap.insertAdjacentHTML('beforeend', add.dataset.tpl === 'src' ? SRC_TPL : AB_TPL);
        }
        var rm = e.target.closest('[data-repeater-remove]');
        if (rm) rm.closest('.repeater-row').remove();
    });
    // optimization checklist (advisory)
    var list = document.getElementById('seo-checklist');
    if (list) {
        var checks = [
            ['SEO title set (or clear default)', function () { return document.getElementById('seo-title').value.trim() !== '' || document.querySelector('[name="title"], [name="name"]').value.trim() !== ''; }],
            ['Meta description 50–160 chars', function () { var v = document.getElementById('seo-meta').value.trim(); return v.length >= 50 && v.length <= 160; }],
            ['Focus topic set', function () { return document.getElementById('seo-focus').value.trim() !== ''; }],
            ['Quick answer written (AEO)', function () { return document.getElementById('quick-answer').value.trim().length >= 40; }],
            ['At least one source attached (GEO)', function () { return document.querySelectorAll('[name="src_name[]"]').length > 0 && document.querySelector('[name="src_name[]"]').value.trim() !== ''; }],
            ['Answer block or FAQ present (AEO)', function () { return document.querySelectorAll('[name="ab_question[]"]').length > 0 || (document.querySelector('[name="faq"]') && document.querySelector('[name="faq"]').value.trim() !== ''); }],
            ['Canonical + robots set (defaults OK)', function () { return true; }],
            ['Social image set (OG image or featured)', function () { return document.getElementById('og-image').value.trim() !== '' || (document.querySelector('[name="featured_image"], [name="main_image"]') || {}).value !== ''; }]
        ];
        var render = function () {
            list.innerHTML = checks.map(function (c) {
                var ok; try { ok = c[1](); } catch (err) { ok = false; }
                return '<li class="' + (ok ? 'ok' : 'todo') + '">' + (ok ? '✓' : '○') + ' ' + c[0] + '</li>';
            }).join('');
        };
        list.closest('form').addEventListener('input', render); render();
    }
})();
</script>
<style>
.seo-checklist{list-style:none;padding:0;display:grid;gap:.3rem}
.seo-checklist li{padding:.35rem .6rem;border-radius:8px;background:var(--color-surface-alt,#eef0f4);font-size:.88rem}
.seo-checklist li.ok{color:#1a7f4e}.seo-checklist li.todo{color:#b7791f}
</style>
<?php
}

/** Persist SEO extras (sources + answer blocks) for an entity. */
function save_seo_extras(string $entityType, int $entityId): void
{
    db()->prepare('DELETE FROM content_sources WHERE entity_type = ? AND entity_id = ?')->execute([$entityType, $entityId]);
    $names = $_POST['src_name'] ?? [];
    foreach ($names as $i => $name) {
        $name = trim((string)$name);
        if ($name === '') continue;
        db()->prepare('INSERT INTO content_sources (entity_type, entity_id, name, url, source_type, accessed_date, sort_order) VALUES (?,?,?,?,?,?,?)')
            ->execute([$entityType, $entityId, mb_substr($name, 0, 190), trim((string)($_POST['src_url'][$i] ?? '')) ?: null,
                       in_array($_POST['src_type'][$i] ?? '', ['manufacturer','government','regulator','research','official_documentation','independent_testing','other'], true) ? $_POST['src_type'][$i] : 'other',
                       date('Y-m-d'), (int)$i]);
    }
    db()->prepare('DELETE FROM answer_blocks WHERE entity_type = ? AND entity_id = ?')->execute([$entityType, $entityId]);
    $qs = $_POST['ab_question'] ?? [];
    foreach ($qs as $i => $q) {
        $q = trim((string)$q); $short = trim((string)($_POST['ab_short'][$i] ?? ''));
        if ($q === '' || $short === '') continue;
        db()->prepare('INSERT INTO answer_blocks (entity_type, entity_id, question, short_answer, explanation, sort_order) VALUES (?,?,?,?,?,?)')
            ->execute([$entityType, $entityId, mb_substr($q, 0, 300), $short, trim((string)($_POST['ab_explain'][$i] ?? '')) ?: null, (int)$i]);
    }
}
