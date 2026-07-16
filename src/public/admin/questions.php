<?php
/**
 * Адмін: управління запитаннями (CRUD)
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

startSession();
requireAdmin();

$db         = getDb();
$pageTitle  = 'Запитання — Адмін';
$activePage = 'admin';
$action     = $_GET['action'] ?? 'list';
$editId     = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$categories = getCategories();

// ------ Обробка POST ------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    // --- Видалення ---
    if ($postAction === 'delete') {
        $id   = (int)$_POST['id'];
        $stmt = $db->prepare('DELETE FROM questions WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Запитання видалено.'];
        header('Location: /admin/questions.php');
        exit;
    }

    // --- Додавання / редагування ---
    if (in_array($postAction, ['create', 'update'])) {
        $questionText  = trim($_POST['question'] ?? '');
        $catId         = (int)($_POST['category_id'] ?? 0);
        $explanation   = trim($_POST['explanation'] ?? '');
        $answerTexts   = $_POST['answer_text']    ?? [];
        $correctAnswer = (int)($_POST['correct_answer'] ?? 0); // індекс 0-3

        $error = '';
        if (empty($questionText))  $error = 'Запитання не може бути порожнім.';
        if (!$catId)               $error = 'Оберіть категорію.';
        if (count($answerTexts) !== 4) $error = 'Потрібно 4 варіанти відповідей.';
        foreach ($answerTexts as $at) {
            if (empty(trim($at))) { $error = 'Всі варіанти відповідей мають бути заповнені.'; break; }
        }
        if (!isset($answerTexts[$correctAnswer])) $error = 'Оберіть правильну відповідь.';

        if (empty($error)) {
            if ($postAction === 'create') {
                // Створити
                $stmt = $db->prepare('INSERT INTO questions (category_id, question, explanation) VALUES (?, ?, ?)');
                $stmt->execute([$catId, $questionText, $explanation]);
                $qId = (int)$db->lastInsertId();

                $stmt = $db->prepare('INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)');
                foreach ($answerTexts as $idx => $text) {
                    $stmt->execute([$qId, trim($text), $idx === $correctAnswer ? 1 : 0]);
                }
                $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Запитання додано.'];
                header('Location: /admin/questions.php');
                exit;
            } else {
                // Оновити
                $qId = (int)$_POST['id'];
                $stmt = $db->prepare('UPDATE questions SET category_id=?, question=?, explanation=? WHERE id=?');
                $stmt->execute([$catId, $questionText, $explanation, $qId]);

                // Видаляємо старі відповіді та додаємо нові
                $db->prepare('DELETE FROM answers WHERE question_id = ?')->execute([$qId]);
                $stmt = $db->prepare('INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)');
                foreach ($answerTexts as $idx => $text) {
                    $stmt->execute([$qId, trim($text), $idx === $correctAnswer ? 1 : 0]);
                }
                $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Запитання оновлено.'];
                header('Location: /admin/questions.php');
                exit;
            }
        }
        // Якщо є помилка — залишаємося на формі
        $_SESSION['flash'][] = ['type' => 'error', 'msg' => $error];
    }
}

// ------ Дані для форм ------
$editQuestion = null;
$editAnswers  = [];
if (in_array($action, ['edit']) && $editId) {
    $stmt = $db->prepare('SELECT * FROM questions WHERE id = ?');
    $stmt->execute([$editId]);
    $editQuestion = $stmt->fetch();
    if ($editQuestion) {
        $editAnswers = getAnswersForQuestion($editId);
    }
}

// ------ Список запитань ------
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 20;
$offset    = ($page - 1) * $perPage;
$catFilter = filter_input(INPUT_GET, 'cat', FILTER_VALIDATE_INT) ?: null;

$where = $catFilter ? 'WHERE q.category_id = ?' : '';
$total = (int)$db->query('SELECT COUNT(*) FROM questions' . ($catFilter ? ' WHERE category_id = ' . $catFilter : ''))->fetchColumn();
$pages = ceil($total / $perPage);

if ($catFilter) {
    $stmt = $db->prepare(
        "SELECT q.*, c.name AS cat_name FROM questions q JOIN categories c ON c.id=q.category_id
         WHERE q.category_id = ? ORDER BY q.id DESC LIMIT ? OFFSET ?"
    );
    $stmt->execute([$catFilter, $perPage, $offset]);
} else {
    $stmt = $db->prepare(
        "SELECT q.*, c.name AS cat_name FROM questions q JOIN categories c ON c.id=q.category_id
         ORDER BY q.id DESC LIMIT ? OFFSET ?"
    );
    $stmt->execute([$perPage, $offset]);
}
$questions = $stmt->fetchAll();

require __DIR__ . '/../templates/header.php';
?>

<div class="container">
<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div>
        <?php if ($action === 'new' || $action === 'edit'): ?>
            <!-- ===== ФОРМА ДОДАВАННЯ / РЕДАГУВАННЯ ===== -->
            <div class="d-flex align-center justify-between mb-2">
                <h2><?= $action === 'new' ? '➕ Нове запитання' : '✏️ Редагувати запитання' ?></h2>
                <a href="/admin/questions.php" class="btn btn-outline btn-sm">← Назад</a>
            </div>

            <div class="card">
                <form method="POST" action="/admin/questions.php">
                    <input type="hidden" name="action" value="<?= $action === 'new' ? 'create' : 'update' ?>">
                    <?php if ($editQuestion): ?>
                        <input type="hidden" name="id" value="<?= $editQuestion['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Категорія</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">— Оберіть категорію —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"
                                    <?= ($editQuestion && $editQuestion['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Запитання</label>
                        <textarea name="question" class="form-control" rows="3" required><?= htmlspecialchars($editQuestion['question'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Пояснення (необов'язково)</label>
                        <textarea name="explanation" class="form-control" rows="2"><?= htmlspecialchars($editQuestion['explanation'] ?? '') ?></textarea>
                        <span class="form-hint">Показується після вибору відповіді</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Варіанти відповідей (оберіть правильну)</label>
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <?php $a = $editAnswers[$i] ?? null; ?>
                            <div class="d-flex align-center gap-1 mb-1">
                                <input type="radio" name="correct_answer" value="<?= $i ?>"
                                    <?= ($a && $a['is_correct']) ? 'checked' : '' ?>
                                    <?= $i === 0 && !$editQuestion ? 'checked' : '' ?>
                                    style="flex-shrink:0;">
                                <input type="text" name="answer_text[]" class="form-control"
                                       value="<?= htmlspecialchars($a['answer_text'] ?? '') ?>"
                                       placeholder="Варіант <?= $i + 1 ?>" required>
                            </div>
                        <?php endfor; ?>
                        <span class="form-hint">⬤ — позначте правильну відповідь</span>
                    </div>

                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-primary">
                            <?= $action === 'new' ? '➕ Додати' : '💾 Зберегти' ?>
                        </button>
                        <a href="/admin/questions.php" class="btn btn-outline">Скасувати</a>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <!-- ===== СПИСОК ЗАПИТАНЬ ===== -->
            <div class="d-flex align-center justify-between flex-wrap gap-1 mb-2">
                <h2>❓ Запитання (<?= $total ?>)</h2>
                <a href="/admin/questions.php?action=new" class="btn btn-primary btn-sm">➕ Додати</a>
            </div>

            <!-- Фільтр за категорією -->
            <form method="GET" class="d-flex gap-1 mb-2 flex-wrap" style="align-items:center;">
                <select name="cat" class="form-control" style="max-width:200px;" onchange="this.form.submit()">
                    <option value="">Всі категорії</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <div class="card">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Категорія</th>
                                <th>Запитання</th>
                                <th>Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($questions)): ?>
                                <tr><td colspan="4" class="text-center text-muted">Запитань немає.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($questions as $q): ?>
                                <tr>
                                    <td><?= $q['id'] ?></td>
                                    <td><span class="badge badge-admin"><?= htmlspecialchars($q['cat_name']) ?></span></td>
                                    <td style="max-width:400px;"><?= htmlspecialchars(mb_strimwidth($q['question'], 0, 80, '…')) ?></td>
                                    <td>
                                        <a href="/admin/questions.php?action=edit&id=<?= $q['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $q['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                    data-confirm="Видалити запитання #<?= $q['id'] ?>?">🗑️</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Пагінація -->
                <?php if ($pages > 1): ?>
                    <div class="pagination mt-2">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <?php $href = '/admin/questions.php?page='.$i.($catFilter?'&cat='.$catFilter:''); ?>
                            <?php if ($i === $page): ?>
                                <span class="active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="<?= $href ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
