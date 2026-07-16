<?php
/**
 * Список вікі-сторінок
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

startSession();

$pageTitle  = 'Вікі';
$activePage = 'wiki';

$pages = getAllWikiPages();

require __DIR__ . '/templates/header.php';
?>

<div class="container">
    <div class="d-flex align-center justify-between flex-wrap gap-1 mb-2">
        <h2>📖 Вікі — Довідник Linux</h2>
        <?php if (isAdmin()): ?>
            <a href="/admin/wiki.php?action=new" class="btn btn-primary btn-sm">➕ Нова стаття</a>
        <?php endif; ?>
    </div>

    <?php if (empty($pages)): ?>
        <div class="card text-center">
            <p class="text-muted">Вікі-сторінок поки немає.</p>
        </div>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;">
            <?php foreach ($pages as $p): ?>
                <a href="/wiki-article.php?slug=<?= urlencode($p['slug']) ?>" class="category-card">
                    <div class="category-icon">📄</div>
                    <div class="category-name"><?= htmlspecialchars($p['title']) ?></div>
                    <div class="category-count">
                        Автор: <?= htmlspecialchars($p['author_name'] ?? 'Невідомо') ?>
                        &bull; <?= date('d.m.Y', strtotime($p['updated_at'])) ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
