<?php
/**
 * Перегляд окремої вікі-статті
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

startSession();

$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    header('Location: /wiki.php');
    exit;
}

$page = getWikiBySlug($slug);
if (!$page) {
    http_response_code(404);
    $pageTitle  = 'Сторінку не знайдено';
    $activePage = 'wiki';
    require __DIR__ . '/templates/header.php';
    echo '<div class="container"><div class="card text-center"><h2>404</h2><p>Вікі-сторінку не знайдено.</p><a href="/wiki.php" class="btn btn-outline">← Назад до вікі</a></div></div>';
    require __DIR__ . '/templates/footer.php';
    exit;
}

$pageTitle  = $page['title'];
$activePage = 'wiki';

require __DIR__ . '/templates/header.php';
?>

<div class="container">
    <!-- Навігація назад -->
    <a href="/wiki.php" class="btn btn-outline btn-sm mb-2">← Всі статті</a>

    <div class="card">
        <div class="d-flex align-center justify-between flex-wrap gap-1 mb-2">
            <div>
                <h1 style="font-size:1.6rem;margin:0 0 .3rem;"><?= htmlspecialchars($page['title']) ?></h1>
                <p class="text-muted text-sm mb-0">
                    Автор: <?= htmlspecialchars($page['author_name'] ?? 'Невідомо') ?>
                    &bull; Оновлено: <?= formatDate($page['updated_at']) ?>
                </p>
            </div>
            <?php if (isAdmin()): ?>
                <a href="/admin/wiki.php?action=edit&id=<?= $page['id'] ?>" class="btn btn-outline btn-sm">✏️ Редагувати</a>
            <?php endif; ?>
        </div>

        <!-- Зміст статті (trusted HTML від адміна) -->
        <div class="wiki-content">
            <?= $page['content'] ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
