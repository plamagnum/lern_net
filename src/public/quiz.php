<?php
/**
 * Сторінка тестування (Quiz)
 * Показує вибір категорії та запускає інтерактивний тест
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

startSession();
requireLogin(); // Тільки для авторизованих

$pageTitle  = 'Тест';
$activePage = 'quiz';

$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: null;
$categories = getCategories();

// Отримуємо назву обраної категорії
$selectedCategory = null;
if ($categoryId) {
    $selectedCategory = getCategoryById($categoryId);
    if (!$selectedCategory) {
        $categoryId = null;
    }
}

require __DIR__ . '/templates/header.php';
?>

<div class="container">
    <div class="quiz-container">

        <!-- Вибір категорії -->
        <div class="card mb-2">
            <div class="d-flex align-center justify-between flex-wrap gap-1">
                <div>
                    <h2 class="mb-0">📝 Тест</h2>
                    <?php if ($selectedCategory): ?>
                        <p class="text-muted text-sm mb-0">Категорія: <strong><?= htmlspecialchars($selectedCategory['name']) ?></strong></p>
                    <?php else: ?>
                        <p class="text-muted text-sm mb-0">Змішаний тест — усі категорії</p>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-1 flex-wrap">
                    <!-- Вибір категорії -->
                    <form method="GET" style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
                        <select name="category" class="form-control" style="min-width:160px;" onchange="this.form.submit()">
                            <option value="">— Всі категорії —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $categoryId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <a href="/quiz.php<?= $categoryId ? '?category='.$categoryId : '' ?>" class="btn btn-outline btn-sm">🔄 Новий тест</a>
                </div>
            </div>
        </div>

        <!-- Контейнер для тесту (заповнюється JS) -->
        <div id="quiz-container">
            <div class="text-center">
                <p class="text-muted">Натисніть кнопку, щоб розпочати тест</p>
                <button class="btn btn-primary btn-lg"
                        onclick="initQuiz(<?= $categoryId ? $categoryId : 'null' ?>)">
                    🚀 Почати тест
                </button>
            </div>
        </div>

    </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
