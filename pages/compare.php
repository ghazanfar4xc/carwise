<?php
/** AutoPulse — car comparison tool (Module 12). Server renders shell + picks; JS builds the table via /api/compare.php. */
seo_set([
    'title' => 'Compare Cars — Side-by-Side Specs',
    'description' => 'Compare up to three cars side by side: price, engine, power, torque, economy, dimensions and features — differences highlighted automatically.',
]);
$brands = get_brands();
$initialIds = array_filter(array_map('intval', explode(',', (string)($_GET['ids'] ?? ''))));

include __DIR__ . '/../includes/header.php';
?>
<header class="page-head">
    <div class="container">
        <?php render_breadcrumbs([['name' => 'Home', 'url' => ''], ['name' => 'Compare Cars']]); ?>
        <h1>Compare Cars</h1>
        <p>Pick two or three models — meaningful differences are highlighted automatically.</p>
    </div>
</header>

<div class="section">
    <div class="container">
        <div class="compare-picker" id="compare-picker" data-initial="<?= e(implode(',', $initialIds)) ?>">
            <div class="form-field" style="margin:0">
                <label for="cmp-brand">Brand</label>
                <select id="cmp-brand" class="select">
                    <option value="">Select brand…</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= (int)$b['id'] ?>" data-slug="<?= e($b['slug']) ?>"><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field" style="margin:0">
                <label for="cmp-model">Model</label>
                <select id="cmp-model" class="select" disabled>
                    <option value="">Select model…</option>
                </select>
            </div>
            <div style="display:flex;gap:.6rem;align-items:end">
                <button type="button" id="cmp-add" class="btn btn-primary" disabled>Add to compare</button>
                <button type="button" id="cmp-clear" class="btn btn-outline">Clear</button>
            </div>
        </div>

        <div id="compare-status" role="status" aria-live="polite"></div>
        <div id="compare-results">
            <div class="empty-state">
                <div class="icon">⚖️</div>
                <h3>Select cars to compare</h3>
                <p>Choose a brand and model above, or press “+ Compare” on any car card.</p>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
