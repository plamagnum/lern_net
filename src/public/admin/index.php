<?php
/**
 * Адмін-панель: головна сторінка зі статистикою
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

startSession();
requireAdmin();

$pageTitle  = 'Адмін-панель';
$activePage = 'admin';
$stats      = getAdminStats();

require __DIR__ . '/../templates/header.php';
?>

<div class="container">
    <div class="admin-layout">

        <!-- Бічна панель навігації -->
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Основний контент -->
        <div>
            <h2>⚙️ Адмін-панель</h2>

            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['questions'] ?></div>
                    <div class="stat-label">Запитань</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['users'] ?></div>
                    <div class="stat-label">Користувачів</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['results'] ?></div>
                    <div class="stat-label">Результатів</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['wiki'] ?></div>
                    <div class="stat-label">Вікі-сторінок</div>
                </div>
            </div>

            <!-- Швидкий доступ -->
            <div class="card">
                <h3 class="card-title">Швидкий доступ</h3>
                <div class="d-flex gap-1 flex-wrap">
                    <a href="/admin/questions.php?action=new" class="btn btn-primary">➕ Нове запитання</a>
                    <a href="/admin/categories.php?action=new" class="btn btn-outline">📁 Нова категорія</a>
                    <a href="/admin/wiki.php?action=new" class="btn btn-outline">📄 Нова вікі-стаття</a>
                    <a href="/admin/users.php" class="btn btn-outline">👥 Користувачі</a>
                </div>
            </div>

            <!-- Інформація про API -->
            <div class="card">
                <h3 class="card-title">🔌 REST API</h3>
                <p class="text-sm text-muted">Для додавання запитань через API використовуйте ключ:</p>
                <code style="background:var(--bg-secondary);padding:.4rem .7rem;border-radius:var(--radius-sm);font-size:.88rem;display:inline-block;"><?= htmlspecialchars(ADMIN_API_KEY) ?></code>
                <p class="text-sm text-muted mt-1">Приклад: <a href="/api/questions.php?api_key=<?= urlencode(ADMIN_API_KEY) ?>" target="_blank">GET /api/questions.php?api_key=...</a></p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
