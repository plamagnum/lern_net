<?php
/**
 * Сторінка результатів тестів поточного користувача
 * CRUD результатів: перегляд та видалення
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

startSession();
requireLogin();

$pageTitle  = 'Мої результати';
$activePage = 'results';
$currentUser = getCurrentUser();

// Обробка видалення результату
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    $deleted  = deleteQuizResult($deleteId, $currentUser['id'], isAdmin());
    $_SESSION['flash'][] = $deleted
        ? ['type' => 'success', 'msg' => 'Результат видалено.']
        : ['type' => 'error',   'msg' => 'Помилка видалення.'];
    header('Location: /results.php');
    exit;
}

$results = getUserResults($currentUser['id'], 50);

require __DIR__ . '/templates/header.php';
?>

<div class="container">
    <div class="d-flex align-center justify-between flex-wrap gap-1 mb-2">
        <h2>📊 Мої результати</h2>
        <a href="/quiz.php" class="btn btn-primary">📝 Новий тест</a>
    </div>

    <?php if (empty($results)): ?>
        <div class="card text-center">
            <p class="text-muted">Ви ще не проходили жодного тесту.</p>
            <a href="/quiz.php" class="btn btn-primary">Почати перший тест</a>
        </div>
    <?php else: ?>

        <!-- Загальна статистика -->
        <?php
        $avgScore  = count($results) ? round(array_sum(array_column($results, 'score')) / count($results), 1) : 0;
        $bestScore = count($results) ? max(array_column($results, 'score')) : 0;
        ?>
        <div class="stats-grid mb-2" style="max-width:500px;">
            <div class="stat-card">
                <div class="stat-number"><?= count($results) ?></div>
                <div class="stat-label">Тестів пройдено</div>
            </div>
            <div class="stat-card">
                <div class="stat-number <?= scoreClass((float)$avgScore) ?>"><?= $avgScore ?>%</div>
                <div class="stat-label">Середній бал</div>
            </div>
            <div class="stat-card">
                <div class="stat-number <?= scoreClass((float)$bestScore) ?>"><?= $bestScore ?>%</div>
                <div class="stat-label">Кращий результат</div>
            </div>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Категорія</th>
                            <th>Результат</th>
                            <th>Бал</th>
                            <th>Дія</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $r): ?>
                            <tr>
                                <td><?= formatDate($r['completed_at']) ?></td>
                                <td><?= htmlspecialchars($r['category_name'] ?? 'Змішаний') ?></td>
                                <td><?= $r['correct'] ?> / <?= $r['total'] ?></td>
                                <td>
                                    <span class="<?= scoreClass((float)$r['score']) ?>" style="font-weight:600;">
                                        <?= $r['score'] ?>%
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                data-confirm="Видалити цей результат?">
                                            🗑️
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>
