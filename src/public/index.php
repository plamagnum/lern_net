<?php
/**
 * Головна сторінка LinuxTest
 * Показує категорії та запрошує до тестування
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

startSession();

$pageTitle  = 'Головна';
$activePage = 'home';

// Отримуємо категорії з кількістю запитань
$db = getDb();
$categories = $db->query(
    'SELECT c.*, COUNT(q.id) AS question_count
     FROM categories c
     LEFT JOIN questions q ON q.category_id = c.id
     GROUP BY c.id
     ORDER BY c.name'
)->fetchAll();

// Статистика для гостей
$totalQuestions = (int)$db->query('SELECT COUNT(*) FROM questions')->fetchColumn();
$totalUsers     = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();

$currentUser = getCurrentUser();

require __DIR__ . '/templates/header.php';
?>

<div class="container">
    <!-- Hero-секція -->
    <div class="hero">
        <h1>Перевір свої знання Linux! 🐧</h1>
        <p>Інтерактивні тести з файлових операцій, мережевих команд, прав доступу та багато іншого. Вчись, практикуйся, вдосконалюйся.</p>

        <?php if ($currentUser): ?>
            <a href="/quiz.php" class="btn btn-primary btn-lg">📝 Почати тест</a>
            <a href="/results.php" class="btn btn-outline btn-lg">📊 Мої результати</a>
        <?php else: ?>
            <a href="/register.php" class="btn btn-primary btn-lg">🚀 Почати безкоштовно</a>
            <a href="/login.php" class="btn btn-outline btn-lg">🔑 Увійти</a>
        <?php endif; ?>
    </div>

    <!-- Загальна статистика -->
    <div class="stats-grid" style="max-width:500px;margin:0 auto 2rem;">
        <div class="stat-card">
            <div class="stat-number"><?= $totalQuestions ?></div>
            <div class="stat-label">Запитань</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count($categories) ?></div>
            <div class="stat-label">Категорій</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $totalUsers ?></div>
            <div class="stat-label">Користувачів</div>
        </div>
    </div>

    <!-- Категорії -->
    <h2 class="text-center mb-2">Теми тестів</h2>

    <!-- Іконки для категорій -->
    <?php
    $icons = [
        'Файлова система'    => '📁',
        'Мережа / iptables'  => '🔒',
        'Права доступу'      => '🛡️',
        'Процеси'            => '⚙️',
        'Текстові утиліти'   => '📄',
        'Системне адмінів.'  => '🖥️',
    ];
    ?>

    <div class="category-grid">
        <?php foreach ($categories as $cat): ?>
            <a href="/quiz.php?category=<?= $cat['id'] ?>" class="category-card">
                <div class="category-icon"><?= $icons[$cat['name']] ?? '📌' ?></div>
                <div class="category-name"><?= htmlspecialchars($cat['name']) ?></div>
                <div class="category-count"><?= $cat['question_count'] ?> запитань</div>
            </a>
        <?php endforeach; ?>
        <!-- Тест з усіх категорій -->
        <a href="/quiz.php" class="category-card">
            <div class="category-icon">🎯</div>
            <div class="category-name">Змішаний тест</div>
            <div class="category-count">Усі категорії</div>
        </a>
    </div>

    <!-- Вікі секція -->
    <div class="card mt-3">
        <div class="d-flex align-center justify-between flex-wrap gap-1">
            <div>
                <h3 class="mb-0">📖 Вікі</h3>
                <p class="text-muted text-sm mb-0">Довідник команд та утиліт Linux</p>
            </div>
            <a href="/wiki.php" class="btn btn-outline">Відкрити вікі →</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
